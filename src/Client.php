<?php

namespace Aternos\Etcd;

use Aternos\Etcd\Exception\ResponseStatusCodeException;
use Etcdserverpb\Compare;
use Etcdserverpb\Compare\CompareResult;
use Etcdserverpb\Compare\CompareTarget;
use Etcdserverpb\KVClient;
use Etcdserverpb\PutRequest;
use Etcdserverpb\PutResponse;
use Etcdserverpb\RangeResponse;
use Etcdserverpb\RequestOp;
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
    private $hostname;
    /**
     * @var bool
     */
    private $username;
    /**
     * @var bool
     */
    private $password;

    /**
     * @var \Etcdserverpb\KVClient
     */
    private $kvClient;

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
     * @param $value
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

        $request = new \Etcdserverpb\PutRequest();

        $request->setKey($key);
        $request->setValue($value);
        $request->setPrevKv($prevKv);
        $request->setIgnoreLease($ignoreLease);
        $request->setIgnoreValue($ignoreValue);
        $request->setLease($lease);

        /** @var PutResponse $response */
        list($response, $status) = $client->Put($request)->wait();

        if ($status->code !== 0) {
            throw new ResponseStatusCodeException(false, $status->code);
        }

        if ($prevKv) {
            return $response->getPrevKv()->getValue();
        }
    }

    /**
     * Get a key value
     *
     * @param string $key
     * @return bool|mixed
     * @throws ResponseStatusCodeException
     */
    public function get(string $key)
    {
        $client = $this->getKvClient();

        $request = new \Etcdserverpb\RangeRequest();
        $request->setKey($key);

        /** @var RangeResponse $response */
        list($response, $status) = $client->Range($request)->wait();

        if ($status->code !== 0) {
            throw new ResponseStatusCodeException(false, $status->code);
        }

        $field = $response->getKvs();

        if (count($field) === 0) {
            return false;
        }

        return $field[0]->getValue();
    }

    /**
     * Swap a value (put it only if it matches $previousValue)
     *
     * @param string $key
     * @param $value
     * @param $previousValue
     * @return bool
     * @throws ResponseStatusCodeException
     */
    public function swap(string $key, $value, $previousValue)
    {
        $client = $this->getKvClient();

        $compare = new Compare();
        $compare->setKey($key);
        $compare->setValue($previousValue);
        $compare->setResult(CompareResult::EQUAL);
        $compare->setTarget(CompareTarget::VALUE);

        $putRequest = new PutRequest();
        $putRequest->setKey($key);
        $putRequest->setValue($value);

        $operation = new RequestOp();
        $operation->setRequestPut($putRequest);

        $request = new TxnRequest();
        $request->setCompare([$compare]);
        $request->setSuccess([$operation]);

        /** @var TxnResponse $response */
        list($response, $status) = $client->Txn($request)->wait();

        if ($status->code !== 0) {
            throw new ResponseStatusCodeException(false, $status->code);
        }

        return $response->getSucceeded();
    }

    /**
     * Get an instance of KVClient
     *
     * @return KVClient
     */
    private function getKvClient(): KVClient
    {
        if (!$this->kvClient) {
            $this->kvClient = new KVClient($this->hostname, [
                'credentials' => ChannelCredentials::createInsecure()
            ]);
        }

        return $this->kvClient;
    }
}