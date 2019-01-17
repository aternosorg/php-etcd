<?php

namespace Aternos\Etcd\Exception;

use Throwable;

/**
 * Class InvalidArgumentException
 *
 * @author Matthias Neid
 * @package Aternos\Etcd\Exception
 */
class InvalidArgumentException extends ResponseStatusCodeException
{
    /**
     * PermissionDeniedException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}