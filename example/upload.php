<?php

declare(strict_types=1);

use Prismic\Asset\Client;

require __DIR__ . '/functions.php';

$client = getClient();
assert($client instanceof Client);

$filePath = __DIR__ . '/example-image.jpg';
$contents = file_get_contents($filePath);
assert($contents !== false && $contents !== '');

$asset = $client->uploadAsset(
    $contents,
    'example-image.jpg',
    'image/jpeg',
    'No notes here…',
    'Some stuff',
    'An example image',
    ['Delete Me'],
);

printf(
    <<<'TEXT'
    Uploaded %d bytes to %s
    
    URL: %s
    ID: %s
    
    TEXT,
    $asset->size,
    $asset->filename,
    $asset->url,
    $asset->id,
);
