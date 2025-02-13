<?php

declare(strict_types=1);

use Prismic\Asset\Client;

require __DIR__ . '/functions.php';

$client = getClient();
assert($client instanceof Client);

$filePath = __DIR__ . '/asset-list.json';

$assets = $client->listAssets();

$written = file_put_contents($filePath, json_encode($assets, JSON_THROW_ON_ERROR));
if ($written === false) {
    throw new RuntimeException('Failed to write file');
}

printf(
    "Wrote %d bytes to %s\n",
    $written,
    $filePath,
);
