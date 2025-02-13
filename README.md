# Prismic Asset API PHP Client

This is a client for Prismic.io's Asset API.

You can find documentation on the API at [prismic.io/docs/asset-api-technical-reference](https://prismic.io/docs/asset-api-technical-reference)

## Install

```bash
composer require netglue/prismic-asset-api-client
```

## Usage

You'll need to construct the concrete client yourself with all of its required dependencies:

- A [PSR-18](https://www.php-fig.org/psr/psr-18/) HTTP Client such as [php-http/curl-client](https://github.com/php-http/curl-client)
- [PSR-7](https://www.php-fig.org/psr/psr-7/) implementations with [PSR-17](https://www.php-fig.org/psr/psr-17/) Stream Factory, Request Factory and Uri Factory such as [laminas/laminas-diactoros](https://github.com/laminas/laminas-diactoros/)

Assuming you have a PSR-11 container set up, you might be able to do something like this in a factory:

```php
use Prismic\Asset\AssetClient;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

$client = new AssetClient(
    'some-write-token',
    'some-repo-name',
    $container->get(ClientInterface::class),
    $container->get(RequestFactoryInterface::class),
    $container->get(UriFactoryInterface::class),
    $container->get(StreamFactoryInterface::class),
);
```

## Basic Functionality

### Fetch a list of the current asset tags

```php
use Prismic\Asset\Client;

assert($client instanceof Client);
$tags = $client->getTags();
```

### Manually add an asset tag

```php
use Prismic\Asset\Client;

assert($client instanceof Client);
$tags = $client->createTag('Some Tag');
```

### List Assets

This library assumes that you'll probably want to use it for doing a migration, so the `listAssets` method fetches _all_ asset details in batches of 10 _(The max result set size)_.
This is likely to take a long time on a large media library…

```php
use Prismic\Asset\Client;
use Prismic\Asset\Model\Asset;

assert($client instanceof Client);

/** @var list<Asset> $assets */
$assets = $client->listAssets();
```

### Upload Assets

The `uploadAsset` method requires the file content as a string, a name or title for the file and its mime type.
There are a number of optional fields for other file metadata such as its default `alt` attribute, private notes, copyright info and applied tags.

```php
use Prismic\Asset\Client;

assert($client instanceof Client);

$asset = $client->uploadAsset(
    $fileContentsAsString,
    $fileBaseName,
    $mimeType,
);
```

### Update Asset Meta Data

All values except the identifier are optional. Replacing any other value with `null` will omit the field from the patch entirely.
To remove values, you must supply empty strings, or in the case of tags, an empty array.

It is not possible to "nullify" asset properties, even sending null in the JSON payload will yield an asset with an empty string for that value.

```php
use Prismic\Asset\Client;use Prismic\Asset\Model\AssetPatch;

assert($client instanceof Client);

$updatedAsset = $client->patchAssetMetaData(new AssetPatch(
    'some-asset-identifier',
    'New private notes content',
    'New copyright info',
    'New alt attribute',
    'updated-filename.txt',
    ['tag 1', 'tag 2'],
));
```

### Tags are automatically created

During asset upload, or patch, tags are created automatically so both methods accept a list of tag names i.e. `['tag 1', 'tag 2']` saving you from mucking about with tag lists and tag id's.

## Contributing

Please feel free to get involved with development. The project uses PHPUnit for tests, and [Psalm](https://psalm.dev) and [PHPStan](https://phpstan.org) for static analysis. CI should have your back if you want to submit a feature or fix ;)

## License

[MIT Licensed](LICENSE.md).
