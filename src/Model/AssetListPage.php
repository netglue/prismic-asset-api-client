<?php

declare(strict_types=1);

namespace Prismic\Asset\Model;

/** @psalm-api */
final readonly class AssetListPage
{
    /**
     * @param int<0, max>           $total
     * @param list<Asset>           $items
     * @param non-empty-string|null $cursor
     */
    public function __construct(
        public int $total,
        public array $items,
        public string|null $cursor,
    ) {
    }
}
