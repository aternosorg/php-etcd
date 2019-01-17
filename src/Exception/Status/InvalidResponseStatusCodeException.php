<?php

namespace Aternos\Etcd\Exception\Status;

use Throwable;

/**
 * Class InvalidResponseStatusCodeException
 *
 * @author Matthias Neid
 */
class InvalidResponseStatusCodeException extends \Exception
{
    /**
     * InvalidResponseStatusCodeException constructor.
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