<?php

namespace Aternos\Etcd;

use Aternos\Etcd\Exception\InvalidClientException;
use Aternos\Etcd\Exception\NoClientAvailableException;
use Aternos\Etcd\Exception\NoResponseException;
use Aternos\Etcd\Exception\Status\DeadlineExceededException;
use Aternos\Etcd\Exception\Status\UnavailableException;
use Etcdserverpb\Compare;
use Etcdserverpb\RequestOp;
use Etcdserverpb\TxnResponse;
use Generator;

/**
 * Class FailoverClient
 *
 * Using random-based load-balancing by default.
 * Client is being used, until it fails when LB turned off. When Client fails,
 * it's moved to the end of the array and first Client on top of the array is being used.
 * When Client's remote call fails, Client's fail counter increases.
 * On top of it each Client gets fail timestamp in case it reaches max retry value (default 3).
 * We are not using such client until hold-off period passes (default 120s).
 * If no usable Client is left, NoClientAvailableException is thrown.
 *
 * @package Aternos\Etcd
 */
class FailoverClient implements ClientInterface
{
    /**
     * How long to keep Client marked as failed and evicted from active client's list
     * in seconds
     *
     * @var int
     */
    protected $holdoffTime = 120;

    /**
     * How many times to retry client request
     *
     * @var int
     */
    protected $maxRetry = 3;

    /**
     * @var array
     */
    protected $retry = [];

    /**
     * @var ClientInterface[]
     */
    protected $clients = [];

    /**
     * @var array
     */
    protected $failedClients = [];

    /**
     * @var bool
     */
    protected $balancing = true;

    /**
     * FailoverClient constructor.
     *
     * @param ClientInterface[] $clients
     * @throws InvalidClientException
     */
    public function __construct(array $clients)
    {
        foreach ($clients as $client) {
            if (!$client instanceof ClientInterface) {
                throw new InvalidClientException("Invalid client in the client list");
            }
            $this->clients[$client->getHostname()] = $client;
        }
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function getHostname(?string $key = null): string
    {
        return $this->callClientMethod(__FUNCTION__, true, $key);
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function put(string $key, $value, bool $prevKv = false, int $leaseID = 0, bool $ignoreLease = false, bool $ignoreValue = false)
    {
        return $this->callClientMethod(__FUNCTION__, false, $key, $value, $prevKv, $leaseID, $ignoreLease, $ignoreValue);
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function get(string $key)
    {
        return $this->callClientMethod(__FUNCTION__, false, $key);
    }

    /**
     * @param string $prefix
     * @param int $limit
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function getWithPrefix(string $prefix, int $limit = 100): Generator
    {
        return $this->callClientMethod(__FUNCTION__, false, $prefix, $limit);
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function delete(string $key)
    {
        return $this->callClientMethod(__FUNCTION__, false, $key);
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function putIf(string $key, string $value, $compareValue, bool $returnNewValueOnFail = false)
    {
        return $this->callClientMethod(__FUNCTION__, false, $key, $value, $compareValue, $returnNewValueOnFail);
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function deleteIf(string $key, $compareValue, bool $returnNewValueOnFail = false)
    {
        return $this->callClientMethod(__FUNCTION__, false, $key, $compareValue, $returnNewValueOnFail);
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function txnRequest(array $requestOperations, ?array $failureOperations, array $compare): TxnResponse
    {
        return $this->callClientMethod(__FUNCTION__, false, $requestOperations, $failureOperations, $compare);
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function getCompare(string $key, string $value, int $result, int $target): Compare
    {
        return $this->callClientMethod(__FUNCTION__, true, $key, $value, $result, $target);
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function getGetOperation(string $key): RequestOp
    {
        return $this->callClientMethod(__FUNCTION__, true, $key);
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function getPutOperation(string $key, string $value, int $leaseId = 0): RequestOp
    {
        return $this->callClientMethod(__FUNCTION__, true, $key, $value, $leaseId);
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function getDeleteOperation(string $key): RequestOp
    {
        return $this->callClientMethod(__FUNCTION__, true, $key);
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function getLeaseID(int $ttl)
    {
        return $this->callClientMethod(__FUNCTION__, false, $ttl);
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function revokeLeaseID(int $leaseID)
    {
        $this->callClientMethod(__FUNCTION__, false, $leaseID);
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function refreshLease(int $leaseID)
    {
        return $this->callClientMethod(__FUNCTION__, false, $leaseID);
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function getResponses(TxnResponse $txnResponse, ?string $type = null, bool $simpleArray = false): array
    {
        return $this->callClientMethod(__FUNCTION__, true, $txnResponse, $type, $simpleArray);
    }

    /**
     * Change holdoff period for failing client
     *
     * @param int $holdoffTime
     */
    public function setHoldoffTime(int $holdoffTime)
    {
        $this->holdoffTime = $holdoffTime;
    }

    /**
     * Change maxRetry value for failing clients
     *
     * @param int $maxRetry
     */
    public function setMaxRetry(int $maxRetry)
    {
        $this->maxRetry = $maxRetry;
    }

    /**
     * Enables or disables balancing between etcd nodes, default is true
     *
     * @param bool $balancing
     */
    public function setBalancing(bool $balancing)
    {
        $this->balancing = $balancing;
    }

    protected function failClient(ClientInterface $client)
    {
        if (isset($this->retry[$client->getHostname()])) {
            $this->retry[$client->getHostname()]++;
        } else {
            $this->retry[$client->getHostname()] = 1;
        }
        if ($this->retry[$client->getHostname()] >= $this->maxRetry) {
            $this->failedClients[] = ['client' => $client, 'time' => time()];
            unset($this->clients[$client->getHostname()]);
        }
    }

    /**
     * @return ClientInterface
     */
    protected function getRandomClient(): ClientInterface
    {
        $rndIndex = (string)array_rand($this->clients);
        return $this->clients[$rndIndex];
    }

    /**
     * Returns first ClientInterface or null if no available
     *
     * @return ClientInterface|null
     */
    protected function getFirstClient(): ?ClientInterface
    {
        if (($client = current($this->clients)) !== false)
            return $client;

        return null;
    }

    /**
     * @return ClientInterface
     * @throws NoClientAvailableException
     */
    protected function getClient(): ClientInterface
    {
        foreach ($this->failedClients as $failedClient) {
            if ((time() - $failedClient['time']) > $this->holdoffTime) {
                $c = array_shift($this->failedClients);
                /** @var ClientInterface $client */
                $client = $c['client'];
                $this->clients[$client->getHostname()] = $client;
            }
        }

        if ($client = $this->getFirstClient())
            return ($this->balancing) ? $this->getRandomClient() : $client;

        throw new NoClientAvailableException('Could not get any working etcd server');
    }


    /**
     * @param string $name Client method
     * @param mixed $arguments method's arguments
     * @param bool $isLocalCall defines whether called method calls remote etcd endpoint
     * @return mixed
     * @throws NoClientAvailableException when there is no available etcd client
     */
    protected function callClientMethod(string $name, bool $isLocalCall, ...$arguments)
    {
        while ($client = $this->getClient()) {
            try {
                $result = $client->$name(...$arguments);
                if (isset($this->retry[$client->getHostname()]) && !$isLocalCall)
                    unset($this->retry[$client->getHostname()]);

                return $result;
            } /** @noinspection PhpRedundantCatchClauseInspection */
            catch (UnavailableException | DeadlineExceededException | NoResponseException $e) {
                $this->failClient($client);
            }
        }
    }
}
