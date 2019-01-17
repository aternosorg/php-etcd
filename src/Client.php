<?php

namespace Aternos\Etcd;

use Aternos\Etcd\Exception\ResponseStatusCodeException;
use Aternos\Etcd\Exception\ResponseStatusCodeExceptionFactory;
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
class Client
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
     * @var \Etcdserverpb\KVClient
     */
    protected $kvClient;

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
     * @throws ResponseStatusCodeException
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
        list($response, $status) = $client->Put($request)->wait();

        if ($status->code !== 0) {
            throw ResponseStatusCodeExceptionFactory::getExceptionByCode($status->code, $status->details);
        }

        if ($prevKv) {
            return $response->getPrevKv()->getValue();
        }
    }

    /**
     * Get a key value
     *
     * @param string $key
     * @return bool|string
     * @throws ResponseStatusCodeException
     */
    public function get(string $key)
    {
        $client = $this->getKvClient();

        $request = new RangeRequest();
        $request->setKey($key);

        /** @var RangeResponse $response */
        list($response, $status) = $client->Range($request)->wait();

        if ($status->code !== 0) {
            throw ResponseStatusCodeExceptionFactory::getExceptionByCode($status->code, $status->details);
        }

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
     * @throws ResponseStatusCodeException
     */
    public function delete(string $key)
    {
        $client = $this->getKvClient();

        $request = new DeleteRangeRequest();
        $request->setKey($key);

        /** @var DeleteRangeResponse $response */
        list($response, $status) = $client->DeleteRange($request)->wait();

        if ($status->code !== 0) {
            throw ResponseStatusCodeExceptionFactory::getExceptionByCode($status->code, $status->details);
        }

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
     * @throws ResponseStatusCodeException
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
     * @throws ResponseStatusCodeException
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
     * @throws ResponseStatusCodeException
     */
    protected function requestIf(string $key, $previousValue, RequestOp $requestOperation, bool $returnNewValueOnFail = false)
    {
        $client = $this->getKvClient();

        $compare = new Compare();
        $compare->setKey($key);
        $compare->setValue($previousValue);
        $compare->setResult(CompareResult::EQUAL);
        $compare->setTarget(CompareTarget::VALUE);

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
        list($response, $status) = $client->Txn($request)->wait();

        if ($status->code !== 0) {
            throw ResponseStatusCodeExceptionFactory::getExceptionByCode($status->code, $status->details);
        }

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
}