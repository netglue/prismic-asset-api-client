<?php

declare(strict_types=1);

namespace Prismic\Asset\Model;

use function array_filter;

final readonly class AssetPatch
{
    /**
     * Represents a payload to patch a media asset
     *
     * Null values are omitted from the payload, so use empty strings in the patch to remove field values.
     *
     * Prismic returns and accepts an empty string for string values. To clear tags, pass an empty array.
     *
     * @param non-empty-string            $id       The asset to update
     * @param string|null                 $notes    Optional notes
     * @param string|null                 $credits  Optional copyright/credits
     * @param string|null                 $alt      Optional alt text
     * @param string|null                 $filename Optional alteration to the file name
     * @param list<non-empty-string>|null $tags     Optional: This should be a list of tag names
     */
    public function __construct(
        public string $id,
        public string|null $notes = null,
        public string|null $credits = null,
        public string|null $alt = null,
        public string|null $filename = null,
        public array|null $tags = null,
    ) {
    }

    /**
     * Returns the patch payload omitting null values
     *
     * @return array{
     *     notes?: string,
     *     credits?: string,
     *     alt?: string,
     *     filename?: string,
     *     tags?: list<non-empty-string>,
     * }
     *
     * @psalm-suppress LessSpecificReturnStatement, MoreSpecificReturnType
     */
    public function toArray(): array
    {
        return array_filter(
            [
                'notes' => $this->notes,
                'credits' => $this->credits,
                'alt' => $this->alt,
                'filename' => $this->filename,
                'tags' => $this->tags,
            ],
            static fn (mixed $value): bool => $value !== null,
        );
    }
}
