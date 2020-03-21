<?php

namespace Aternos\Etcd;

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
use Etcdserverpb\PutRequest;
use Etcdserverpb\PutResponse;
use Etcdserverpb\RangeRequest;
use Etcdserverpb\RangeResponse;
use Etcdserverpb\RequestOp;
use Etcdserverpb\ResponseOp;
use Etcdserverpb\TxnRequest;
use Etcdserverpb\TxnResponse;
use Grpc\ChannelCredentials;

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
     * Client constructor.
     *
     * @param string $hostname
     * @param bool $username
     * @param bool $password
     */
    public function __construct($hostname = "localhost:2379", $username = false, $password = false)
    {
        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;
    }

    public function getHostname(): string
    {
        return $this->hostname;
    }

    /**
     * Put a value into the key store
     *
     * @param string $key
     * @param mixed $value
     * @param bool $prevKv Get the previous key value in the response
     * @param int $lease
     * @param bool $ignoreLease Ignore the current lease
     * @param bool $ignoreValue Updates the key using its current value
     *
     * @return string|null Returns previous value if $prevKv is set to true
     * @throws InvalidResponseStatusCodeException
     */
    public function put(string $key, $value, bool $prevKv = false, int $lease = 0, bool $ignoreLease = false, bool $ignoreValue = false)
    {
        $client = $this->getKvClient();

        $request = new PutRequest();

        $request->setKey($key);
        $request->setValue($value);
        $request->setPrevKv($prevKv);
        $request->setIgnoreLease($ignoreLease);
        $request->setIgnoreValue($ignoreValue);
        $request->setLease($lease);

        /** @var PutResponse $response */
        list($response, $status) = $client->Put($request, $this->getMetaData())->wait();
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
        list($response, $status) = $client->Range($request, $this->getMetaData())->wait();
        $this->validateStatus($status);

        $field = $response->getKvs();

        if (count($field) === 0) {
            return false;
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
    public function delete(string $key)
    {
        $client = $this->getKvClient();

        $request = new DeleteRangeRequest();
        $request->setKey($key);

        /** @var DeleteRangeResponse $response */
        list($response, $status) = $client->DeleteRange($request, $this->getMetaData())->wait();
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
     * @param mixed $value The new value to set
     * @param mixed $previousValue The previous value to compare against
     * @param bool $returnNewValueOnFail
     * @return bool|string
     * @throws InvalidResponseStatusCodeException
     */
    public function putIf(string $key, $value, $previousValue, bool $returnNewValueOnFail = false)
    {
        $request = new PutRequest();
        $request->setKey($key);
        $request->setValue($value);

        $operation = new RequestOp();
        $operation->setRequestPut($request);

        return $this->requestIf($key, $previousValue, $operation, $returnNewValueOnFail);
    }

    /**
     * Delete if $key value matches $previous value otherwise $returnNewValueOnFail
     *
     * @param string $key
     * @param $previousValue
     * @param bool $returnNewValueOnFail
     * @return bool|string
     * @throws InvalidResponseStatusCodeException
     */
    public function deleteIf(string $key, $previousValue, bool $returnNewValueOnFail = false)
    {
        $request = new DeleteRangeRequest();
        $request->setKey($key);

        $operation = new RequestOp();
        $operation->setRequestDeleteRange($request);

        return $this->requestIf($key, $previousValue, $operation, $returnNewValueOnFail);
    }

    /**
     * Execute $requestOperation if $key value matches $previous otherwise $returnNewValueOnFail
     *
     * @param string $key
     * @param $previousValue
     * @param RequestOp $requestOperation
     * @param bool $returnNewValueOnFail
     * @return bool|string
     * @throws InvalidResponseStatusCodeException
     */
    protected function requestIf(string $key, $previousValue, RequestOp $requestOperation, bool $returnNewValueOnFail = false)
    {
        $client = $this->getKvClient();

        $compare = new Compare();
        $compare->setKey($key);

        if ($previousValue === false) {
            $compare->setValue("0");
            $compare->setResult(CompareResult::EQUAL);
            $compare->setTarget(CompareTarget::VERSION);
        } else {
            $compare->setValue($previousValue);
            $compare->setResult(CompareResult::EQUAL);
            $compare->setTarget(CompareTarget::VALUE);
        }

        $request = new TxnRequest();
        $request->setCompare([$compare]);
        $request->setSuccess([$requestOperation]);

        if ($returnNewValueOnFail) {
            $getRequest = new RangeRequest();
            $getRequest->setKey($key);

            $getOperation = new RequestOp();
            $getOperation->setRequestRange($getRequest);
            $request->setFailure([$getOperation]);
        }

        /** @var TxnResponse $response */
        list($response, $status) = $client->Txn($request, $this->getMetaData())->wait();
        $this->validateStatus($status);

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
            list($response, $status) = $client->Authenticate($request)->wait();
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
     * @param $status
     * @throws InvalidResponseStatusCodeException
     */
    protected function validateStatus($status)
    {
        if ($status->code !== 0) {
            throw ResponseStatusCodeExceptionFactory::getExceptionByCode($status->code, $status->details);
        }
    }
}