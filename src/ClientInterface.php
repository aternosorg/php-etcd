<?php

namespace Aternos\Etcd;

use Aternos\Etcd\Exception\Status\InvalidResponseStatusCodeException;
use Etcdserverpb\Compare\CompareTarget;

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
     * @param int $lease
     * @param bool $ignoreLease Ignore the current lease
     * @param bool $ignoreValue Updates the key using its current value
     * @return string|null Returns previous value if $prevKv is set to true
     * @throws InvalidResponseStatusCodeException
     */
    public function put(string $key, $value, bool $prevKv = false, int $lease = 0, bool $ignoreLease = false, bool $ignoreValue = false);

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
     * @param string $compareValue The previous value to compare against
     * @param string $compareOp can be '=', '!=', '>', '<'
     * @param int $compareTarget check constants in the CompareTarget class for available values
     * @param bool $returnNewValueOnFail
     * @return bool|string
     * @throws InvalidResponseStatusCodeException
     */
    public function putIf(string $key, string $value, string $compareValue = '0', string $compareOp = '=', int $compareTarget = CompareTarget::VALUE, bool $returnNewValueOnFail = false);

    /**
     * Delete if $key value matches $previous value otherwise $returnNewValueOnFail
     *
     * @param string $key
     * @param string $compareValue The previous value to compare against
     * @param string $compareOp can be '=', '!=', '>', '<'
     * @param int $compareTarget check constants in the CompareTarget class for available values
     * @param bool $returnNewValueOnFail
     * @return bool|string
     * @throws InvalidResponseStatusCodeException
     * @throws \Exception
     */
    public function deleteIf(string $key, string $compareValue = '0', string $compareOp = '=', int $compareTarget = CompareTarget::VALUE, bool $returnNewValueOnFail = false);
}