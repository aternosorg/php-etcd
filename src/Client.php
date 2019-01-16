<?php

namespace Aternos\Etcd;

use Aternos\Etcd\Exception\ResponseStatusCodeException;
use Etcdserverpb\PutResponse;

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

        /** @var \Etcdserverpb\RangeResponse $response */
        list($response, $status) = $client->Range($request)->wait();

        if ($status->code !== 0) {
            throw new ResponseStatusCodeException(false, $status->code);
        }

        $field = $response->getKvs();

        if(count($field) === 0) {
            return false;
        }

        return $field[0]->getValue();
    }

    /**
     * Get an instance of KVClient
     *
     * @return \Etcdserverpb\KVClient
     */
    private function getKvClient(): \Etcdserverpb\KVClient
    {
        if (!$this->kvClient) {
            $this->kvClient = new \Etcdserverpb\KVClient($this->hostname, [
                'credentials' => \Grpc\ChannelCredentials::createInsecure()
            ]);
        }

        return $this->kvClient;
    }
}