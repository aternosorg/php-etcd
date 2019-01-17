<?php

namespace Aternos\Etcd\Exception;

/**
 * Class ResponseStatusCodeExceptionFactory
 *
 * @author Matthias Neid
 * @package Aternos\Etcd\Exception
 */
class ResponseStatusCodeExceptionFactory
{
    /**
     * @param $code
     * @param bool $message
     * @return ResponseStatusCodeException
     */
    public static function getExceptionByCode($code, $message = false)
    {
        switch ($code) {
            case 3:
                return new InvalidArgumentException($message, $code);
                break;
            default:
                return new ResponseStatusCodeException($message, $code);
        }
    }
}