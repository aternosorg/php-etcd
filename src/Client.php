<?php

namespace Aternos\Etcd;

use Aternos\Etcd\Exception\InvalidLeaseException;
use Aternos\Etcd\Exception\NoResponseException;
use Aternos\Etcd\Exception\Status\InvalidResponseStatusCodeException;
use Aternos\Etcd\Exception\Status\ResponseStatusCodeExceptionFactory;
use Aternos\Etcd\Proto\Etcdserverpb\AuthClient;
use Aternos\Etcd\Proto\Etcdserverpb\AuthenticateRequest;
use Aternos\Etcd\Proto\Etcdserverpb\AuthenticateResponse;
use Aternos\Etcd\Proto\Etcdserverpb\Compare;
use Aternos\Etcd\Proto\Etcdserverpb\Compare\CompareResult;
use Aternos\Etcd\Proto\Etcdserverpb\Compare\CompareTarget;
use Aternos\Etcd\Proto\Etcdserverpb\DeleteRangeRequest;
use Aternos\Etcd\Proto\Etcdserverpb\DeleteRangeResponse;
use Aternos\Etcd\Proto\Etcdserverpb\KVClient;
use Aternos\Etcd\Proto\Etcdserverpb\LeaseClient;
use Aternos\Etcd\Proto\Etcdserverpb\LeaseGrantRequest;
use Aternos\Etcd\Proto\Etcdserverpb\LeaseGrantResponse;
use Aternos\Etcd\Proto\Etcdserverpb\LeaseKeepAliveRequest;
use Aternos\Etcd\Proto\Etcdserverpb\LeaseKeepAliveResponse;
use Aternos\Etcd\Proto\Etcdserverpb\LeaseRevokeRequest;
use Aternos\Etcd\Proto\Etcdserverpb\PutRequest;
use Aternos\Etcd\Proto\Etcdserverpb\PutResponse;
use Aternos\Etcd\Proto\Etcdserverpb\RangeRequest;
use Aternos\Etcd\Proto\Etcdserverpb\RangeResponse;
use Aternos\Etcd\Proto\Etcdserverpb\RequestOp;
use Aternos\Etcd\Proto\Etcdserverpb\ResponseOp;
use Aternos\Etcd\Proto\Etcdserverpb\TxnRequest;
use Aternos\Etcd\Proto\Etcdserverpb\TxnResponse;
use Grpc\ChannelCredentials;
use Aternos\Etcd\Proto\Mvccpb\KeyValue;

/**
 * Class Client
 *
 * @author Matthias Neid
 */
class Client implements ClientInterface
{
    protected ?string $token = null;
    protected ?KVClient $kvClient = null;
    protected ?AuthClient $authClient = null;
    protected ?LeaseClient $leaseClient = null;

    /**
     * Client constructor.
     *
     * @param string $hostname
     * @param string|null $username
     * @param string|null $password
     * @param int $timeout in microseconds, default 1 second
     */
    public function __construct(
        protected string  $hostname = "localhost:2379",
        protected ?string $username = null,
        protected ?string $password = null,
        protected int     $timeout = 1000000
    )
    {
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
     * @param string $value
     * @param bool $prevKv Get the previous key value in the response
     * @param int $leaseID
     * @param bool $ignoreLease Ignore the current lease
     * @param bool $ignoreValue Updates the key using its current value
     *
     * @return string|null Returns previous value if $prevKv is set to true
     * @throws InvalidResponseStatusCodeException
     */
    public function put(string $key, string $value, bool $prevKv = false, int $leaseID = 0, bool $ignoreLease = false, bool $ignoreValue = false): ?string
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
        list($response, $status) = $client->Put($request, $this->getMetaData(), $this->getOptions())->wait();
        $this->validateStatus($status);

        if ($prevKv) {
            return $response->getPrevKv()?->getValue();
        }

        return null;
    }

    /**
     * Get a key value
     *
     * @param string $key
     * @return string|null null if the key does not exist
     * @throws InvalidResponseStatusCodeException
     */
    public function get(string $key): ?string
    {
        $client = $this->getKvClient();

        $request = new RangeRequest();
        $request->setKey($key);

        /** @var RangeResponse $response */
        list($response, $status) = $client->Range($request, $this->getMetaData(), $this->getOptions())->wait();
        $this->validateStatus($status);

        $field = $response->getKvs();

        if (count($field) === 0) {
            return null;
        }

        return $field[0]->getValue();
    }

    /**
     * Delete a key
     *
     * @param string $key
     * @return bool
     * @throws InvalidResponseStatusCodeException
     */
    public function delete(string $key): bool
    {
        $client = $this->getKvClient();

        $request = new DeleteRangeRequest();
        $request->setKey($key);

        /** @var DeleteRangeResponse $response */
        list($response, $status) = $client->DeleteRange($request, $this->getMetaData(), $this->getOptions())->wait();
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
     * @param string|false $compareValue The previous value to compare against, false if the key must not exist
     * @param bool $returnNewValueOnFail
     * @return bool|string|null true on success; with $returnNewValueOnFail the current value on failure (null if the key does not exist), otherwise false
     * @throws InvalidResponseStatusCodeException
     */
    public function putIf(string $key, string $value, string|false $compareValue, bool $returnNewValueOnFail = false): bool|string|null
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
     * @param string|false $compareValue The previous value to compare against, false if the key must not exist
     * @param bool $returnNewValueOnFail
     * @return bool|string|null true on success; with $returnNewValueOnFail the current value on failure (null if the key does not exist), otherwise false
     * @throws InvalidResponseStatusCodeException
     */
    public function deleteIf(string $key, string|false $compareValue, bool $returnNewValueOnFail = false): bool|string|null
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
    public function getLeaseID(int $ttl): int
    {
        $lease = $this->getLeaseClient();
        $leaseRequest = new LeaseGrantRequest();
        $leaseRequest->setTTL($ttl);

        /** @var LeaseGrantResponse $response */
        list($response, $status) = $lease->LeaseGrant($leaseRequest, $this->getMetaData())->wait();
        $this->validateStatus($status);

        return (int)$response->getID();
    }

    /**
     * Revoke existing leaseID
     *
     * @param int $leaseID
     * @throws InvalidResponseStatusCodeException
     */
    public function revokeLeaseID(int $leaseID): void
    {
        $lease = $this->getLeaseClient();
        $leaseRequest = new LeaseRevokeRequest();
        $leaseRequest->setID($leaseID);

        list(, $status) = $lease->LeaseRevoke($leaseRequest, $this->getMetaData())->wait();
        $this->validateStatus($status);
    }

    /**
     * Refresh chosen leaseID
     *
     * @param int $leaseID
     * @return int lease TTL
     * @throws InvalidResponseStatusCodeException|NoResponseException|InvalidLeaseException
     */
    public function refreshLease(int $leaseID): int
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
        list($response, $status) = $client->Txn($request, $this->getMetaData(), $this->getOptions())->wait();
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
            list($response, $status) = $client->Authenticate($request, [], $this->getOptions())->wait();
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
    protected function getMetaData(array $metadata = []): array
    {
        if (($this->username ?? "") !== "" && ($this->password ?? "") !== "") {
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
    protected function getOptions(array $options = []): array
    {
        return array_merge(["timeout" => $this->timeout], $options);
    }

    /**
     * @param object $status
     * @throws InvalidResponseStatusCodeException
     */
    protected function validateStatus(object $status): void
    {
        if ($status->code !== 0) {
            throw ResponseStatusCodeExceptionFactory::getExceptionByCode($status->code, $status->details);
        }
    }

    /**
     * @param string $key
     * @param string|false $compareValue
     * @return Compare
     */
    protected function getCompareForIf(string $key, string|false $compareValue): Compare
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
     * @return bool|string|null
     */
    protected function getIfResponse(bool $returnNewValueOnFail, TxnResponse $response): bool|string|null
    {
        if ($returnNewValueOnFail && !$response->getSucceeded()) {
            /** @var ResponseOp $responseOp */
            $responseOp = $response->getResponses()[0];

            $getResponse = $responseOp->getResponseRange();

            $field = $getResponse->getKvs();

            if (count($field) === 0) {
                return null;
            }

            return $field[0]->getValue();
        } else {
            return $response->getSucceeded();
        }
    }

    /**
     * @param string $key
     * @param bool $returnNewValueOnFail
     * @return RequestOp[]|null
     */
    protected function getFailOperation(string $key, bool $returnNewValueOnFail): ?array
    {
        $failOperation = null;
        if ($returnNewValueOnFail)
            $failOperation = [$this->getGetOperation($key)];

        return $failOperation;
    }
}
