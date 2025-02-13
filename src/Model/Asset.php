<?php

declare(strict_types=1);

namespace Prismic\Asset\Model;

/**
 * Represents an asset as described by the Prismic Asset API
 *
 * phpcs:disable Squiz.NamingConventions.ValidVariableName
 *
 * @psalm-api
 */
final readonly class Asset
{
    /**
     * @param non-empty-string      $id
     * @param non-empty-string      $url
     * @param int<0, max>           $size
     * @param int<1, max>|null      $width
     * @param int<1, max>|null      $height
     * @param int<1, max>           $last_modified
     * @param non-empty-string      $kind
     * @param non-empty-string|null $uploader_id
     * @param int<1, max>           $created_at
     * @param list<AssetTag>        $tags
     */
    public function __construct(
        public string $id,
        public string $url,
        public string $filename,
        public int $size,
        public int|null $width,
        public int|null $height,
        public int $last_modified,
        public string $kind,
        public string|null $extension,
        public string|null $uploader_id,
        public int $created_at,
        public array $tags,
        public string|null $notes,
        public string|null $credits,
        public string|null $alt,
    ) {
    }
}
