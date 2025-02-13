<?php

declare(strict_types=1);

namespace Prismic\Asset\Model;

/**
 * Represents a tag applied to a media asset
 *
 * phpcs:disable Squiz.NamingConventions.ValidVariableName
 *
 * @psalm-api
 */
final readonly class AssetTag
{
    /**
     * @param non-empty-string      $id            A UUID
     * @param non-empty-string      $name          The tag name
     * @param int<1, max>           $last_modified JS Timestamp
     * @param int<1, max>           $created_at    JS Timestamp
     * @param non-empty-string|null $uploader_id   The user ID who created the tag (if any)
     * @param int<0, max>|null      $count         The number of media items using this tag
     */
    public function __construct(
        public string $id,
        public string $name,
        public int $last_modified,
        public int $created_at,
        public string|null $uploader_id,
        public int|null $count,
    ) {
    }
}
