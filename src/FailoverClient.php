<?php

namespace Aternos\Etcd;

use Aternos\Etcd\Exception\InvalidClientException;
use Aternos\Etcd\Exception\Status\InvalidResponseStatusCodeException;
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
 * @method string getHostname()
 * @method put(string $key, $value, bool $prevKv = false, int $leaseID = 0, bool $ignoreLease = false, bool $ignoreValue = false)
 * @method get(string $key)
 * @method delete(string $key)
 * @method putIf(string $key, string $value, $compareValue, bool $returnNewValueOnFail = false)
 * @method deleteIf(string $key, $compareValue, bool $returnNewValueOnFail = false)
 * @method TxnResponse txnRequest(array $requestOperations, ?array $failureOperations, array $compare)
 * @method Compare getCompare(string $key, string $value, int $result, int $target)
 * @method RequestOp getGetOperation(string $key)
 * @method RequestOp getPutOperation(string $key, string $value, int $leaseId = 0)
 * @method RequestOp getDeleteOperation(string $key)
 * @method int getLeaseID(int $ttl)
 * @method revokeLeaseID(int $leaseID)
 * @method int refreshLease(int $leaseID)
 * @method array getResponses(TxnResponse $txnResponse, ?string $type = null, bool $simpleArray = false)
 *
 * @package Aternos\Etcd
 */
class FailoverClient
{
    /**
     * How long to keep Client marked as failed and evicted from active client's list
     * in seconds
     *
     * @var int
     */
    protected $holdoffTime = 120;

    /**
     * @var ClientInterface[]
     */
    protected $clients = [];

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
            $this->addClient($client);
        }
    }

    /**
     * @param string $name Client method
     * @param mixed $arguments method's arguments
     * @return mixed
     * @throws \Exception when there is no available etcd client
     */
    public function __call(string $name, $arguments)
    {
        while ($client = $this->getClient()) {
            try {
                return $client->$name(...$arguments);
            } /** @noinspection PhpRedundantCatchClauseInspection */
            catch (InvalidResponseStatusCodeException $e) {
                $this->failCurrentClient();
            }
        }
        throw new \Exception('Failed to call: ' . $name);
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
     * @param ClientInterface $client
     */
    protected function addClient(ClientInterface $client)
    {
        $this->clients[] = ['client' => $client];
    }

    protected function failCurrentClient()
    {
        $this->clients[0]['fail'] = time();
        $t = array_shift($this->clients);
        $this->clients[] = $t;
    }

    /**
     * @return ClientInterface
     * @throws \Exception
     */
    protected function getClient(): ClientInterface
    {
        if (!isset($this->clients[0]['fail']) || ((time() - $this->clients[0]['fail']) > $this->holdoffTime))
            return $this->clients[0]['client'];

        throw new \Exception('Could not get any working etcd server');
    }
}