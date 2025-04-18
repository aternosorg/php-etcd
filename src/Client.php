<?php

namespace Aternos\Etcd;

use Aternos\Etcd\Exception\InvalidLeaseException;
use Aternos\Etcd\Exception\NoResponseException;
use Aternos\Etcd\Exception\Status\InvalidResponseStatusCodeException;
use Aternos\Etcd\Exception\Status\ResponseStatusCodeExceptionFactory;
use Etcdserverpb\AuthClient;
use Etcdserverpb\AuthenticateRequest;
use Etcdserverpb\AuthenticateResponse;
use Etcdserverpb\Compare;
use Etcdserverpb\Compare\CompareResult;
use Etcdserverpb\Compare\CompareTarget;
use Etcdserverpb\DeleteRangeRequest;
use Etcdserverpb\DeleteRangeResponse;
use Etcdserverpb\KVClient;
use Etcdserverpb\LeaseClient;
use Etcdserverpb\LeaseGrantRequest;
use Etcdserverpb\LeaseGrantResponse;
use Etcdserverpb\LeaseKeepAliveRequest;
use Etcdserverpb\LeaseKeepAliveResponse;
use Etcdserverpb\LeaseRevokeRequest;
use Etcdserverpb\PutRequest;
use Etcdserverpb\PutResponse;
use Etcdserverpb\RangeRequest;
use Etcdserverpb\RangeResponse;
use Etcdserverpb\RequestOp;
use Etcdserverpb\ResponseOp;
use Etcdserverpb\TxnRequest;
use Etcdserverpb\TxnResponse;
use Generator;
use Grpc\ChannelCredentials;
use Mvccpb\KeyValue;

/**
 * Class Client
 *
 * @author Matthias Neid
 */
class Client implements ClientInterface
{
    /**
     * @var string
     */
    protected $hostname;
    /**
     * @var bool
     */
    protected $username;
    /**
     * @var bool
     */
    protected $password;

    /**
     * @var int
     */
    protected $timeout;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var KVClient
     */
    protected $kvClient;

    /**
     * @var AuthClient
     */
    protected $authClient;

    /**
     * @var LeaseClient
     */
    protected $leaseClient;

    /**
     * Client constructor.
     *
     * @param string $hostname
     * @param bool $username
     * @param bool $password
     * @param int $timeout in microseconds, default 1 second
     */
    public function __construct($hostname = "localhost:2379", $username = false, $password = false, $timeout = 1000000)
    {
        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;
        $this->timeout = $timeout;
    }

    /**
     * @param string|null $key
     * @return string
     */
    public function getHostname(?string $key = null): string
    {
        return $this->hostname;
    }

    /**
     * Put a value into the key store
     *
     * @param string $key
     * @param mixed $value
     * @param bool $prevKv Get the previous key value in the response
     * @param int $leaseID
     * @param bool $ignoreLease Ignore the current lease
     * @param bool $ignoreValue Updates the key using its current value
     *
     * @return string|null Returns previous value if $prevKv is set to true
     * @throws InvalidResponseStatusCodeException
     */
    public function put(string $key, $value, bool $prevKv = false, int $leaseID = 0, bool $ignoreLease = false, bool $ignoreValue = false)
    {
        $client = $this->getKvClient();

        $request = new PutRequest();

        $request->setKey($key);
        $request->setValue($value);
        $request->setPrevKv($prevKv);
        $request->setIgnoreLease($ignoreLease);
        $request->setIgnoreValue($ignoreValue);
        $request->setLease($leaseID);

        /** @var PutResponse $response */
        [$response, $status] = $client->Put($request, $this->getMetaData(), $this->getOptions())->wait();
        $this->validateStatus($status);

        if ($prevKv) {
            return $response->getPrevKv()->getValue();
        }
    }

    /**
     * Get a key value
     *
     * @param string $key
     * @return bool|string
     * @throws InvalidResponseStatusCodeException
     */
    public function get(string $key)
    {
        $client = $this->getKvClient();

        $request = new RangeRequest();
        $request->setKey($key);

        /** @var RangeResponse $response */
        [$response, $status] = $client->Range($request, $this->getMetaData(), $this->getOptions())->wait();
        $this->validateStatus($status);

        $field = $response->getKvs();

        if (count($field) === 0) {
            return false;
        }

        return $field[0]->getValue();
    }

    /**
     * Get range of values by key prefix
     *
     * @param string $prefix
     * @param int $limit
     * @return Generator<KeyValue>
     * @throws InvalidResponseStatusCodeException
     */
    public function getWithPrefix(string $prefix, int $limit = 100): Generator
    {
        $client = $this->getKvClient();
        $rangeEnd = $prefix."\xff";

        do{
            $request = (new RangeRequest())
                ->setKey($prefix)
                ->setRangeEnd($rangeEnd)
                ->setLimit($limit);

            /** @var RangeResponse $response */
            [$response, $status] = $client->Range($request, $this->getMetaData(), $this->getOptions())->wait();
            $this->validateStatus($status);

            $field = $response->getKvs();
            if (count($field) === 0) {
                return;
            }

            foreach ($field as $kv) {
                yield $kv;
            }

            if(!isset($kv)) {
                return;
            }
            $prefix = $kv->getKey()."\x00";
        }while (true);
    }

    /**
     * Delete a key
     *
     * @param string $key
     * @return bool
     * @throws InvalidResponseStatusCodeException
     */
    public function delete(string $key)
    {
        $client = $this->getKvClient();

        $request = new DeleteRangeRequest();
        $request->setKey($key);

        /** @var DeleteRangeResponse $response */
        [$response, $status] = $client->DeleteRange($request, $this->getMetaData(), $this->getOptions())->wait();
        $this->validateStatus($status);

        if ($response->getDeleted() > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Put $value if $key value matches $previousValue otherwise $returnNewValueOnFail
     *
     * @param string $key
     * @param string $value The new value to set
     * @param bool|string $compareValue The previous value to compare against
     * @param bool $returnNewValueOnFail
     * @return bool|string
     * @throws InvalidResponseStatusCodeException
     */
    public function putIf(string $key, string $value, $compareValue, bool $returnNewValueOnFail = false)
    {
        $operation = $this->getPutOperation($key, $value);
        $compare = $this->getCompareForIf($key, $compareValue);
        $failOperation = $this->getFailOperation($key, $returnNewValueOnFail);

        $response = $this->txnRequest([$operation], $failOperation, [$compare]);
        return $this->getIfResponse($returnNewValueOnFail, $response);
    }

    /**
     * Delete if $key value matches $previous value otherwise $returnNewValueOnFail
     *
     * @param string $key
     * @param bool|string $compareValue The previous value to compare against
     * @param bool $returnNewValueOnFail
     * @return bool|string
     * @throws InvalidResponseStatusCodeException
     */
    public function deleteIf(string $key, $compareValue, bool $returnNewValueOnFail = false)
    {
        $operation = $this->getDeleteOperation($key);
        $compare = $this->getCompareForIf($key, $compareValue);
        $failOperation = $this->getFailOperation($key, $returnNewValueOnFail);

        $response = $this->txnRequest([$operation], $failOperation, [$compare]);
        return $this->getIfResponse($returnNewValueOnFail, $response);
    }

    /**
     * Get leaseID which can be used with etcd's put
     *
     * @param int $ttl time-to-live in seconds
     * @return int
     * @throws InvalidResponseStatusCodeException
     */
    public function getLeaseID(int $ttl)
    {
        $lease = $this->getLeaseClient();
        $leaseRequest = new LeaseGrantRequest();
        $leaseRequest->setTTL($ttl);

        /** @var LeaseGrantResponse $response */
        [$response, $status] = $lease->LeaseGrant($leaseRequest, $this->getMetaData())->wait();
        $this->validateStatus($status);

        return (int)$response->getID();
    }

    /**
     * Revoke existing leaseID
     *
     * @param int $leaseID
     * @throws InvalidResponseStatusCodeException
     */
    public function revokeLeaseID(int $leaseID)
    {
        $lease = $this->getLeaseClient();
        $leaseRequest = new LeaseRevokeRequest();
        $leaseRequest->setID($leaseID);

        [, $status] = $lease->LeaseRevoke($leaseRequest, $this->getMetaData())->wait();
        $this->validateStatus($status);
    }

    /**
     * Refresh chosen leaseID
     *
     * @param int $leaseID
     * @return int lease TTL
     * @throws InvalidResponseStatusCodeException|NoResponseException|InvalidLeaseException
     */
    public function refreshLease(int $leaseID)
    {
        $lease = $this->getLeaseClient();
        $leaseBidi = $lease->LeaseKeepAlive($this->getMetaData());
        $leaseKeepAlive = new LeaseKeepAliveRequest();
        $leaseKeepAlive->setID($leaseID);
        /** @noinspection PhpParamsInspection */
        $leaseBidi->write($leaseKeepAlive);
        $leaseBidi->writesDone();
        /** @var LeaseKeepAliveResponse $response */
        $response = $leaseBidi->read();
        $leaseBidi->cancel();
        if($response === null || empty($response->getID()) || (int)$response->getID() !== $leaseID)
            throw new NoResponseException('Could not refresh lease ID: ' . $leaseID);

        if((int)$response->getTTL() === 0)
            throw new InvalidLeaseException('Invalid lease ID or expired lease');

        return (int)$response->getTTL();
    }

    /**
     * Execute $requestOperations if Compare succeeds, execute $failureOperations otherwise if defined
     *
     * @param RequestOp[] $requestOperations operations to perform on success, array of RequestOp objects
     * @param RequestOp[]|null $failureOperations operations to perform on failure, array of RequestOp objects
     * @param Compare[] $compare array of Compare objects
     * @return TxnResponse
     * @throws InvalidResponseStatusCodeException
     */
    public function txnRequest(array $requestOperations, ?array $failureOperations, array $compare): TxnResponse
    {
        $client = $this->getKvClient();

        $request = new TxnRequest();
        $request->setCompare($compare);
        $request->setSuccess($requestOperations);
        if($failureOperations !== null)
            $request->setFailure($failureOperations);

        /** @var TxnResponse $response */
        [$response, $status] = $client->Txn($request, $this->getMetaData(), $this->getOptions())->wait();
        $this->validateStatus($status);

        return $response;
    }

    /**
     * Transform TxnResponse into more friendly array
     *
     * @param TxnResponse $txnResponse return value of txnRequest method
     * @param string|null $type returns only chosen type if defined,
     *                          can be one of those: response_range, response_put, response_delete_range, response_txn
     * @param bool $simpleArray return just simple array containing values,
     *                          example: ['value1', 'value2']
     * @return array example: [
     *                          [
     *                           'type' => 'response_range',
     *                           'values' => [
     *                                        'key' => 'key',
     *                                        'value' => '2',
     *                                        'version' => 3
     *                                        ]
     *                           ]
     *                         ]
     */
    public function getResponses(TxnResponse $txnResponse, ?string $type = null, bool $simpleArray = false): array
    {
        $v = $r = [];
        /** @var ResponseOp $response */
        foreach ($txnResponse->getResponses() as $response) {
            if ($type !== null && $type !== $response->getResponse())
                continue;
            $values = [];
            if ($response->getResponseRange() !== null) {
                /** @var KeyValue $field */
                foreach ($response->getResponseRange()->getKvs() as $field) {
                    $v[] = $field->getValue();
                    $values[] = [
                        'key' => $field->getKey(),
                        'value' => $field->getValue(),
                        'version' => $field->getVersion()
                    ];
                }

            }
            $r[] = ['type' => $response->getResponse(), 'values' => $values];
        }

        return ($simpleArray) ? $v : $r;
    }

    /**
     * Creates RequestOp of Get operation for requestIf method
     *
     * @param string $key
     * @return RequestOp
     */
    public function getGetOperation(string $key): RequestOp
    {
        $request = new RangeRequest();
        $request->setKey($key);

        $operation = new RequestOp();
        $operation->setRequestRange($request);

        return $operation;
    }

    /**
     * Creates RequestOp of Put operation for requestIf method
     *
     * @param string $key
     * @param string $value
     * @param int $leaseId
     * @return RequestOp
     */
    public function getPutOperation(string $key, string $value, int $leaseId = 0): RequestOp
    {
        $request = new PutRequest();
        $request->setKey($key);
        $request->setValue($value);
        if($leaseId !== 0)
            $request->setLease($leaseId);

        $operation = new RequestOp();
        $operation->setRequestPut($request);

        return $operation;
    }

    /**
     * Creates RequestOp of Delete operation for requestIf method
     *
     * @param string $key
     * @return RequestOp
     */
    public function getDeleteOperation(string $key): RequestOp
    {
        $request = new DeleteRangeRequest();
        $request->setKey($key);

        $operation = new RequestOp();
        $operation->setRequestDeleteRange($request);

        return $operation;
    }

    /**
     * Get an instance of Compare
     *
     * @param string $key
     * @param string $value
     * @param int $result see CompareResult class for available constants
     * @param int $target check constants in the CompareTarget class for available values
     * @return Compare
     */
    public function getCompare(string $key, string $value, int $result, int $target): Compare
    {
        $compare = new Compare();
        $compare->setKey($key);
        $compare->setValue($value);
        $compare->setTarget($target);
        $compare->setResult($result);

        return $compare;
    }

    /**
     * Get an instance of LeaseClient
     *
     * @return LeaseClient
     */
    protected function getLeaseClient(): LeaseClient
    {
        if (!$this->leaseClient) {
            $this->leaseClient = new LeaseClient($this->hostname, [
                'credentials' => ChannelCredentials::createInsecure()
            ]);
        }

        return $this->leaseClient;
    }

    /**
     * Get an instance of KVClient
     *
     * @return KVClient
     */
    protected function getKvClient(): KVClient
    {
        if (!$this->kvClient) {
            $this->kvClient = new KVClient($this->hostname, [
                'credentials' => ChannelCredentials::createInsecure()
            ]);
        }

        return $this->kvClient;
    }

    /**
     * Get an instance of AuthClient
     *
     * @return AuthClient
     */
    protected function getAuthClient(): AuthClient
    {
        if (!$this->authClient) {
            $this->authClient = new AuthClient($this->hostname, [
                'credentials' => ChannelCredentials::createInsecure()
            ]);
        }

        return $this->authClient;
    }


    /**
     * Get an authentication token
     *
     * @return string
     * @throws InvalidResponseStatusCodeException
     */
    protected function getAuthenticationToken(): string
    {
        if (!$this->token) {
            $client = $this->getAuthClient();

            $request = new AuthenticateRequest();
            $request->setName($this->username);
            $request->setPassword($this->password);

            /** @var AuthenticateResponse $response */
            [$response, $status] = $client->Authenticate($request, [], $this->getOptions())->wait();
            $this->validateStatus($status);

            $this->token = $response->getToken();
        }

        return $this->token;
    }

    /**
     * Add authentication token metadata if necessary
     *
     * @param array $metadata
     * @return array
     * @throws InvalidResponseStatusCodeException
     */
    protected function getMetaData($metadata = []): array
    {
        if ($this->username && $this->password) {
            $metadata = array_merge(["token" => [$this->getAuthenticationToken()]], $metadata);
        }

        return $metadata;
    }

    /**
     * Add timeout
     *
     * @param array $options
     * @return array
     */
    protected function getOptions($options = []): array
    {
        return array_merge(["timeout" => $this->timeout], $options);
    }

    /**
     * @param $status
     * @throws InvalidResponseStatusCodeException
     */
    protected function validateStatus($status)
    {
        if ($status->code !== 0) {
            throw ResponseStatusCodeExceptionFactory::getExceptionByCode($status->code, $status->details);
        }
    }

    /**
     * @param string $key
     * @param string|bool $compareValue
     * @return Compare
     */
    protected function getCompareForIf(string $key, $compareValue): Compare
    {
        if ($compareValue === false) {
            $compare = $this->getCompare($key, '0', CompareResult::EQUAL, CompareTarget::VERSION);
        } else {
            $compare = $this->getCompare($key, $compareValue, CompareResult::EQUAL, CompareTarget::VALUE);
        }
        return $compare;
    }

    /**
     * @param bool $returnNewValueOnFail
     * @param TxnResponse $response
     * @return bool|string
     */
    protected function getIfResponse(bool $returnNewValueOnFail, TxnResponse $response)
    {
        if ($returnNewValueOnFail && !$response->getSucceeded()) {
            /** @var ResponseOp $responseOp */
            $responseOp = $response->getResponses()[0];

            $getResponse = $responseOp->getResponseRange();

            $field = $getResponse->getKvs();

            if (count($field) === 0) {
                return false;
            }

            return $field[0]->getValue();
        } else {
            return $response->getSucceeded();
        }
    }

    /**
     * @param string $key
     * @param bool $returnNewValueOnFail
     * @return array|null
     */
    protected function getFailOperation(string $key, bool $returnNewValueOnFail)
    {
        $failOperation = null;
        if ($returnNewValueOnFail)
            $failOperation = [$this->getGetOperation($key)];

        return $failOperation;
    }
}
