<?php

declare(strict_types=1);

namespace Prismic\Asset\Exception;

use InvalidArgumentException;

use function sprintf;

final class InvalidTagName extends InvalidArgumentException implements ApiError
{
    public static function for(string $name): self
    {
        return new self(sprintf(
            'Tag names cannot be more than 20 characters long. "%s" does not satisfy those criteria',
            $name,
        ));
    }
}
