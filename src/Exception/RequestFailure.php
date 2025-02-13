<?php

declare(strict_types=1);

namespace Prismic\Asset\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

use function sprintf;

final class RequestFailure extends RuntimeException implements ApiError
{
    public function __construct(
        public readonly RequestInterface $request,
        public readonly ResponseInterface $response,
        string $message,
        int $code,
        Throwable|null $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function fromExchange(
        RequestInterface $request,
        ResponseInterface $response,
    ): self {
        return new self(
            $request,
            $response,
            sprintf(
                'The request to %s failed with a %d status code',
                (string) $request->getUri(),
                $response->getStatusCode(),
            ),
            $response->getStatusCode(),
        );
    }
}
