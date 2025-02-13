<?php

declare(strict_types=1);

namespace Prismic\Asset;

use Prismic\Asset\Model\Asset;
use Prismic\Asset\Model\AssetPatch;
use Prismic\Asset\Model\AssetTag;
use Prismic\Asset\Model\TagCollection;

interface Client
{
    public const string DEFAULT_BASE_URI = 'https://asset-api.prismic.io';

    /**
     * The hard limit imposed in GET /assets
     */
    public const int RESULT_LIMIT = 10;

    /**
     * Fetch a list of all assets
     *
     * Depending on the size of your media library, this will likely take a pretty long time because the limit is 10
     * results per request.
     *
     * @return list<Asset>
     */
    public function listAssets(): array;

    /**
     * Delete a single asset
     *
     * @param non-empty-string $id
     */
    public function deleteAsset(string $id): void;

    /**
     * Upload an asset to the media library
     *
     * @param non-empty-string       $fileContent The file contents as a binary string
     * @param non-empty-string       $fileName    The desired filename, excluding any path information
     * @param non-empty-string       $mimeType    The mime-type of the content to upload
     * @param non-empty-string|null  $notes       Optional, private notes about the file
     * @param non-empty-string|null  $credits     Optional, copyright info
     * @param non-empty-string|null  $alt         Optional, Alt text for images
     * @param list<non-empty-string> $tags        Optional list of tag names to apply to the file
     */
    public function uploadAsset(
        string $fileContent,
        string $fileName,
        string $mimeType,
        string|null $notes = null,
        string|null $credits = null,
        string|null $alt = null,
        array $tags = [],
    ): Asset;

    public function patchAssetMetaData(AssetPatch $patch): Asset;

    /**
     * Fetch the current list of asset tags
     */
    public function getTags(): TagCollection;

    /**
     * Create an asset tag
     *
     * @param non-empty-string $tagName Tag names must be between 3 and 20 characters inclusive
     */
    public function createTag(string $tagName): AssetTag;
}
