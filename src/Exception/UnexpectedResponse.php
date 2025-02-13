<?php

declare(strict_types=1);

namespace Prismic\Asset\Exception;

use RuntimeException;

final class UnexpectedResponse extends RuntimeException implements ApiError
{
}
