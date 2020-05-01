<?php

namespace Aternos\Etcd;

use Aternos\Etcd\Exception\Status\InvalidResponseStatusCodeException;
use Etcdserverpb\Compare;
use Etcdserverpb\RequestOp;
use Etcdserverpb\TxnResponse;
use Exception;

/**
 * Interface ClientInterface
 *
 * @package Aternos\Etcd
 */
interface ClientInterface
{
    /**
     * @param string|null $key
     * @return string
     */
    public function getHostname(?string $key = null): string;

    /**
     * Put a value into the key store
     *
     * @param string $key
     * @param mixed $value
     * @param bool $prevKv Get the previous key value in the response
     * @param int $leaseID
     * @param bool $ignoreLease Ignore the current lease
     * @param bool $ignoreValue Updates the key using its current value
     * @return string|null Returns previous value if $prevKv is set to true
     * @throws InvalidResponseStatusCodeException
     */
    public function put(string $key, $value, bool $prevKv = false, int $leaseID = 0, bool $ignoreLease = false, bool $ignoreValue = false);

    /**
     * Get a key value
     *
     * @param string $key
     * @return bool|string
     * @throws InvalidResponseStatusCodeException
     */
    public function get(string $key);

    /**
     * Delete a key
     *
     * @param string $key
     * @return bool
     * @throws InvalidResponseStatusCodeException
     */
    public function delete(string $key);

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
    public function putIf(string $key, string $value, $compareValue, bool $returnNewValueOnFail = false);

    /**
     * Delete if $key value matches $previous value otherwise $returnNewValueOnFail
     *
     * @param string $key
     * @param bool|string $compareValue The previous value to compare against
     * @param bool $returnNewValueOnFail
     * @return bool|string
     * @throws InvalidResponseStatusCodeException
     * @throws \Exception
     */
    public function deleteIf(string $key, $compareValue, bool $returnNewValueOnFail = false);

    /**
     * Execute $requestOperation if $key value matches $previous otherwise $returnNewValueOnFail
     *
     * @param string $key
     * @param array $requestOperations operations to perform on success, array of RequestOp objects
     * @param array|null $failureOperations operations to perform on failure, array of RequestOp objects
     * @param array $compare array of Compare objects
     * @return TxnResponse
     * @throws InvalidResponseStatusCodeException
     */
    public function txnRequest(string $key, array $requestOperations, ?array $failureOperations, array $compare): TxnResponse;

    /**
     * Get an instance of Compare
     *
     * @param string $key
     * @param string $value
     * @param int $result see CompareResult class for available constants
     * @param int $target check constants in the CompareTarget class for available values
     * @return Compare
     */
    public function getCompare(string $key, string $value, int $result, int $target): Compare;

    /**
     * Creates RequestOp of Get operation for requestIf method
     *
     * @param string $key
     * @return RequestOp
     */
    public function getGetOperation(string $key): RequestOp;

    /**
     * Creates RequestOp of Put operation for requestIf method
     *
     * @param string $key
     * @param string $value
     * @param int $leaseId
     * @return RequestOp
     */
    public function getPutOperation(string $key, string $value, int $leaseId = 0): RequestOp;

    /**
     * Creates RequestOp of Delete operation for requestIf method
     *
     * @param string $key
     * @return RequestOp
     */
    public function getDeleteOperation(string $key): RequestOp;

    /**
     * Get leaseID which can be used with etcd's put
     *
     * @param int $ttl time-to-live in seconds
     * @return int
     * @throws InvalidResponseStatusCodeException
     */
    public function getLeaseID(int $ttl);

    /**
     * Revoke existing leaseID
     *
     * @param int $leaseID
     * @throws InvalidResponseStatusCodeException
     */
    public function revokeLeaseID(int $leaseID);

    /**
     * Refresh chosen leaseID
     *
     * @param int $leaseID
     * @return int lease TTL
     * @throws InvalidResponseStatusCodeException
     * @throws Exception
     */
    public function refreshLease(int $leaseID);
}