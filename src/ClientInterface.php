<?php

namespace Aternos\Etcd;

use Aternos\Etcd\Exception\Status\InvalidResponseStatusCodeException;
use Aternos\Etcd\Proto\Etcdserverpb\Compare;
use Aternos\Etcd\Proto\Etcdserverpb\RequestOp;
use Aternos\Etcd\Proto\Etcdserverpb\TxnResponse;
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
     * @param string $value
     * @param bool $prevKv Get the previous key value in the response
     * @param int $leaseID
     * @param bool $ignoreLease Ignore the current lease
     * @param bool $ignoreValue Updates the key using its current value
     * @return string|null Returns previous value if $prevKv is set to true
     * @throws InvalidResponseStatusCodeException
     */
    public function put(string $key, string $value, bool $prevKv = false, int $leaseID = 0, bool $ignoreLease = false, bool $ignoreValue = false): ?string;

    /**
     * Get a key value
     *
     * @param string $key
     * @return string|null null if the key does not exist
     * @throws InvalidResponseStatusCodeException
     */
    public function get(string $key): ?string;

    /**
     * Delete a key
     *
     * @param string $key
     * @return bool
     * @throws InvalidResponseStatusCodeException
     */
    public function delete(string $key): bool;

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
    public function putIf(string $key, string $value, string|false $compareValue, bool $returnNewValueOnFail = false): bool|string|null;

    /**
     * Delete if $key value matches $previous value otherwise $returnNewValueOnFail
     *
     * @param string $key
     * @param string|false $compareValue The previous value to compare against, false if the key must not exist
     * @param bool $returnNewValueOnFail
     * @return bool|string|null true on success; with $returnNewValueOnFail the current value on failure (null if the key does not exist), otherwise false
     * @throws InvalidResponseStatusCodeException
     * @throws Exception
     */
    public function deleteIf(string $key, string|false $compareValue, bool $returnNewValueOnFail = false): bool|string|null;

    /**
     * Execute $requestOperations if Compare succeeds, execute $failureOperations otherwise if defined
     *
     * @param array $requestOperations operations to perform on success, array of RequestOp objects
     * @param array|null $failureOperations operations to perform on failure, array of RequestOp objects
     * @param array $compare array of Compare objects
     * @return TxnResponse
     * @throws InvalidResponseStatusCodeException
     */
    public function txnRequest(array $requestOperations, ?array $failureOperations, array $compare): TxnResponse;

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
    public function getLeaseID(int $ttl): int;

    /**
     * Revoke existing leaseID
     *
     * @param int $leaseID
     * @throws InvalidResponseStatusCodeException
     */
    public function revokeLeaseID(int $leaseID): void;

    /**
     * Refresh chosen leaseID
     *
     * @param int $leaseID
     * @return int lease TTL
     * @throws InvalidResponseStatusCodeException
     * @throws Exception
     */
    public function refreshLease(int $leaseID): int;

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
    public function getResponses(TxnResponse $txnResponse, ?string $type = null, bool $simpleArray = false): array;
}
