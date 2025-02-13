<?php

declare(strict_types=1);

namespace Prismic\Asset\Exception;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use RuntimeException;
use Throwable;

use function sprintf;

/** @psalm-api */
final class CommunicationFailure extends RuntimeException implements ApiError
{
    public function __construct(
        public RequestInterface $request,
        string $message,
        int $code = 0,
        Throwable|null $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function fromPsrError(RequestInterface $request, ClientExceptionInterface $exception): self
    {
        return new self(
            $request,
            sprintf(
                'The request to %s failed: %s',
                (string) $request->getUri(),
                $exception->getMessage(),
            ),
            $exception->getCode(),
            $exception,
        );
    }
}
