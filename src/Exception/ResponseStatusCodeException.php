<?php

namespace Aternos\Etcd\Exception;

use Throwable;

/**
 * Class ResponseStatusCodeException
 *
 * @author Matthias Neid
 */
class ResponseStatusCodeException extends \Exception
{
    /**
     * ResponseStatusCodeException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        if (!$message) {
            $message = "Invalid response status code from gRPC request: " . $code;
        }

        parent::__construct($message, $code, $previous);
    }
}