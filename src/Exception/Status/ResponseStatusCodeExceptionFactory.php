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
     * @param int $code
     * @param string $message
     * @return InvalidResponseStatusCodeException
     */
    public static function getExceptionByCode(int $code, string $message = ""): InvalidResponseStatusCodeException
    {
        return match ($code) {
            1 => new CanceledException($message, $code),
            2 => new UnknownException($message, $code),
            3 => new InvalidArgumentException($message, $code),
            4 => new DeadlineExceededException($message, $code),
            5 => new NotFoundException($message, $code),
            6 => new AlreadyExistsException($message, $code),
            7 => new PermissionDeniedException($message, $code),
            8 => new ResourceExhaustedException($message, $code),
            9 => new FailedPreconditionException($message, $code),
            10 => new AbortedException($message, $code),
            11 => new OutOfRangeException($message, $code),
            12 => new UnimplementedException($message, $code),
            13 => new InternalException($message, $code),
            14 => new UnavailableException($message, $code),
            15 => new DataLossException($message, $code),
            16 => new UnauthenticatedException($message, $code),
            default => new InvalidResponseStatusCodeException($message, $code),
        };
    }
}
