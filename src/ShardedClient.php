<?php

namespace Aternos\Etcd;

use Aternos\Etcd\Exception\InvalidClientException;
use Flexihash\Flexihash;

/**
 * Class ShardedClient
 *
 * @package Aternos\Etcd
 */
class ShardedClient implements ClientInterface
{
    /**
     * @var ClientInterface[]
     */
    protected $clients = [];

    /**
     * @var ClientInterface[]
     */
    protected $keyCache = [];

    /**
     * @var Flexihash
     */
    protected $hash = null;

    /**
     * ShardedClient constructor.
     *
     * @param ClientInterface[] $clients
     * @throws InvalidClientException
     */
    public function __construct(array $clients)
    {
        foreach ($clients as $client) {
            if (!$client instanceof ClientInterface) {
                throw new InvalidClientException("Invalid client in client list.");
            }

            $this->clients[$client->getHostname()] = $client;
        }
    }

    /**
     * Get the correct client object for that key through consistent hashing
     *
     * @param string $key
     * @return ClientInterface
     * @throws \Flexihash\Exception
     */
    protected function getClientFromKey(string $key): ClientInterface
    {
        if (isset($this->keyCache[$key])) {
            return $this->keyCache[$key];
        }

        if ($this->hash === null) {
            $this->hash = new Flexihash();
            foreach ($this->clients as $client) {
                $this->hash->addTarget($client->getHostname());
            }
        }

        $clientHostname = $this->hash->lookup($key);
        $this->keyCache[$key] = $this->clients[$clientHostname];
        return $this->keyCache[$key];
    }


    public function getHostname(): string
    {
        return implode("-", array_keys($this->clients));
    }

    /**
     * @inheritDoc
     * @throws \Flexihash\Exception
     */
    public function put(string $key, $value, bool $prevKv = false, int $lease = 0, bool $ignoreLease = false, bool $ignoreValue = false)
    {
        return $this->getClientFromKey($key)->put($key, $value, $prevKv, $lease, $ignoreLease, $ignoreValue);
    }

    /**
     * @inheritDoc
     * @throws \Flexihash\Exception
     */
    public function get(string $key)
    {
        return $this->getClientFromKey($key)->get($key);
    }

    /**
     * @inheritDoc
     * @throws \Flexihash\Exception
     */
    public function delete(string $key)
    {
        return $this->getClientFromKey($key)->delete($key);
    }

    /**
     * @inheritDoc
     * @throws \Flexihash\Exception
     */
    public function putIf(string $key, $value, $previousValue, bool $returnNewValueOnFail = false)
    {
        return $this->getClientFromKey($key)->putIf($key, $value, $previousValue, $returnNewValueOnFail);
    }

    /**
     * @inheritDoc
     * @throws \Flexihash\Exception
     */
    public function deleteIf(string $key, $previousValue, bool $returnNewValueOnFail = false)
    {
        return $this->getClientFromKey($key)->deleteIf($key, $previousValue, $returnNewValueOnFail);
    }
}