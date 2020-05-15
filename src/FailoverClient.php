<?php

namespace Aternos\Etcd;

use Aternos\Etcd\Exception\InvalidClientException;
use Aternos\Etcd\Exception\NoClientAvailableException;
use Aternos\Etcd\Exception\Status\DeadlineExceededException;
use Aternos\Etcd\Exception\Status\UnavailableException;
use Etcdserverpb\Compare;
use Etcdserverpb\RequestOp;
use Etcdserverpb\TxnResponse;

/**
 * Class FailoverClient
 *
 * Using FIFO structure. Client is being used, until it fails. When it fails,
 * it's moved to the end of the array and first Client on top of the array is being used.
 * On top of it each Client gets fail timestamp in case it fails. We are not using such client
 * until hold-off period passes. If no usable Client is left, \Exception is thrown.
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
        }
        $this->clients = $clients;
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function getHostname(?string $key = null): string
    {
        return $this->callClientMethod(__FUNCTION__, $key);
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function put(string $key, $value, bool $prevKv = false, int $leaseID = 0, bool $ignoreLease = false, bool $ignoreValue = false)
    {
        return $this->callClientMethod(__FUNCTION__, $key, $value, $prevKv, $leaseID, $ignoreLease, $ignoreValue);
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function get(string $key)
    {
        return $this->callClientMethod(__FUNCTION__, $key);
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function delete(string $key)
    {
        return $this->callClientMethod(__FUNCTION__, $key);
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function putIf(string $key, string $value, $compareValue, bool $returnNewValueOnFail = false)
    {
        return $this->callClientMethod(__FUNCTION__, $key, $value, $compareValue, $returnNewValueOnFail);
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function deleteIf(string $key, $compareValue, bool $returnNewValueOnFail = false)
    {
        return $this->callClientMethod(__FUNCTION__, $key, $compareValue, $returnNewValueOnFail);
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function txnRequest(array $requestOperations, ?array $failureOperations, array $compare): TxnResponse
    {
        return $this->callClientMethod(__FUNCTION__, $requestOperations, $failureOperations, $compare);
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function getCompare(string $key, string $value, int $result, int $target): Compare
    {
        return $this->callClientMethod(__FUNCTION__, $key, $value, $result, $target);
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function getGetOperation(string $key): RequestOp
    {
        return $this->callClientMethod(__FUNCTION__, $key);
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function getPutOperation(string $key, string $value, int $leaseId = 0): RequestOp
    {
        return $this->callClientMethod(__FUNCTION__, $key, $value, $leaseId);
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function getDeleteOperation(string $key): RequestOp
    {
        return $this->callClientMethod(__FUNCTION__, $key);
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function getLeaseID(int $ttl)
    {
        return $this->callClientMethod(__FUNCTION__, $ttl);
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function revokeLeaseID(int $leaseID)
    {
        $this->callClientMethod(__FUNCTION__, $leaseID);
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function refreshLease(int $leaseID)
    {
        return $this->callClientMethod(__FUNCTION__, $leaseID);
    }

    /**
     * @inheritDoc
     * @throws NoClientAvailableException
     */
    public function getResponses(TxnResponse $txnResponse, ?string $type = null, bool $simpleArray = false): array
    {
        return $this->callClientMethod(__FUNCTION__, $txnResponse, $type, $simpleArray);
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

    protected function failCurrentClient()
    {
        $t = array_shift($this->clients);
        if(isset($this->retry[$t->getHostname()])) {
            $this->retry[$t->getHostname()]++;
        }else{
            $this->retry[$t->getHostname()] = 1;
        }
        if($this->retry[$t->getHostname()] >= $this->maxRetry) {
            $this->failedClients[] = ['client' => $t, 'time' => time()];
        }else{
            array_unshift($this->clients, $t);
        }
    }

    /**
     * @return ClientInterface
     */
    protected function getRandomClient(): ClientInterface
    {
        $rndIndex = (int)array_rand($this->clients);
        return $this->clients[$rndIndex];
    }

    /**
     * @return ClientInterface
     * @throws NoClientAvailableException
     */
    protected function getClient(): ClientInterface
    {
        if (count($this->clients) > 0)
            return ($this->balancing) ? $this->getRandomClient() : $this->clients[0];

        if ((time() - $this->failedClients[0]['time']) > $this->holdoffTime) {
            $t = array_shift($this->failedClients);
            $this->clients[] = $t['client'];
            return $this->clients[0];
        }

        throw new NoClientAvailableException('Could not get any working etcd server');
    }

    /**
     * @param string $name Client method
     * @param mixed $arguments method's arguments
     * @return mixed
     * @throws NoClientAvailableException when there is no available etcd client
     * @throws \Exception
     */
    protected function callClientMethod(string $name, ...$arguments)
    {
        while ($client = $this->getClient()) {
            try {
                $result = $client->$name(...$arguments);
                if(isset($this->retry[$client->getHostname()]))
                    unset($this->retry[$client->getHostname()]);

                return $result;
            } /** @noinspection PhpRedundantCatchClauseInspection */
            catch (UnavailableException | DeadlineExceededException | NoResponseException $e) {
                $this->failCurrentClient();
            }
        }
        throw new \Exception('Failed to call: ' . $name);
    }
}