<?php

namespace Aternos\Etcd\Exception\Status;

/**
 * Class ResponseStatusCodeExceptionFactory
 *
 * @author Matthias Neid
 * @package Aternos\Etcd\Exception
 */
class ResponseStatusCodeExceptionFactory
{
    /**
     * Get an object of the correct exception based on the code
     *
     * This is based on https://github.com/grpc/grpc-go/blob/master/codes/codes.go
     *
     * @param $code
     * @param bool $message
     * @return InvalidResponseStatusCodeException
     */
    public static function getExceptionByCode($code, $message = false)
    {
        switch ($code) {
            case 1:
                return new CanceledException($message, $code);
                break;
            case 2:
                return new UnknownException($message, $code);
                break;
            case 3:
                return new InvalidArgumentException($message, $code);
                break;
            case 4:
                return new DeadlineExceededException($message, $code);
                break;
            case 5:
                return new NotFoundException($message, $code);
                break;
            case 6:
                return new AlreadyExistsException($message, $code);
                break;
            case 7:
                return new PermissionDeniedException($message, $code);
                break;
            case 8:
                return new ResourceExhaustedException($message, $code);
                break;
            case 9:
                return new FailedPreconditionException($message, $code);
                break;
            case 10:
                return new AbortedException($message, $code);
                break;
            case 11:
                return new OutOfRangeException($message, $code);
                break;
            case 12:
                return new UnimplementedException($message, $code);
                break;
            case 13:
                return new InternalException($message, $code);
                break;
            case 14:
                return new UnavailableException($message, $code);
                break;
            case 15:
                return new DataLossException($message, $code);
                break;
            case 16:
                return new UnauthenticatedException($message, $code);
                break;
            default:
                return new InvalidResponseStatusCodeException($message, $code);
        }
    }
}