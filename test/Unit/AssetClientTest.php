<?php

declare(strict_types=1);

namespace Prismic\Asset\Test\Unit;

use Fig\Http\Message\RequestMethodInterface;
use Http\Client\Exception\NetworkException;
use Http\Mock\Client;
use Laminas\Diactoros\Request;
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UriFactory;
use Override;
use PHPUnit\Framework\TestCase;
use Prismic\Asset\AssetClient;
use Prismic\Asset\Exception\CommunicationFailure;
use Prismic\Asset\Exception\RequestFailure;
use Prismic\Asset\Exception\UnexpectedResponse;
use Prismic\Asset\Model\AssetPatch;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use function file_get_contents;
use function json_encode;

use const JSON_THROW_ON_ERROR;

class AssetClientTest extends TestCase
{
    private Client $http;
    private AssetClient $client;

    #[Override]
    protected function setUp(): void
    {
        $this->http = new Client();
        $this->client = new AssetClient(
            'token',
            'repo',
            $this->http,
            new RequestFactory(),
            new UriFactory(),
            new StreamFactory(),
        );
    }

    /** @param non-empty-string $filePath */
    private function fixtureResponse(string $filePath): ResponseInterface
    {
        $contents = file_get_contents($filePath);
        self::assertIsString($contents);

        return (new Response())->withBody(
            (new StreamFactory())->createStream($contents),
        );
    }

    public function testPatchAsset(): void
    {
        $this->http->addResponse($this->fixtureResponse(__DIR__ . '/../fixtures/asset-patch.json'));

        $result = $this->client->patchAssetMetaData(new AssetPatch(
            'whatever',
            'notes',
            'credits',
            'alt',
            'filename',
            [],
        ));

        $request = $this->http->getLastRequest();
        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertStringEndsWith(
            '/assets/whatever',
            (string) $request->getUri(),
            'The request uri should end with the patched asset identifier',
        );

        self::assertSame(
            RequestMethodInterface::METHOD_PATCH,
            $request->getMethod(),
            'Expected a PATCH request',
        );

        $requestBody = (string) $request->getBody();
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'notes' => 'notes',
                'credits' => 'credits',
                'alt' => 'alt',
                'filename' => 'filename',
                'tags' => [],
            ], JSON_THROW_ON_ERROR),
            $requestBody,
            'The request body should contain all the expected attributes',
        );

        self::assertSame(
            'Z6zKUJbqstJ9-iXW',
            $result->id,
            'The result payload should match the fixture',
        );
    }

    public function testNullPropertiesAreIgnoredWhenPatchingAssets(): void
    {
        $this->http->addResponse($this->fixtureResponse(__DIR__ . '/../fixtures/asset-patch.json'));

        $result = $this->client->patchAssetMetaData(new AssetPatch(
            'whatever',
            'notes',
        ));

        $request = $this->http->getLastRequest();
        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertStringEndsWith(
            '/assets/whatever',
            (string) $request->getUri(),
            'The request uri should end with the patched asset identifier',
        );

        self::assertSame(
            RequestMethodInterface::METHOD_PATCH,
            $request->getMethod(),
            'Expected a PATCH request',
        );

        $requestBody = (string) $request->getBody();
        self::assertJsonStringEqualsJsonString(
            json_encode(['notes' => 'notes'], JSON_THROW_ON_ERROR),
            $requestBody,
            'The request body should contain all the expected attributes',
        );

        self::assertSame(
            'Z6zKUJbqstJ9-iXW',
            $result->id,
            'The result payload should match the fixture',
        );
    }

    public function testTagsAreResolvedToIdentifiersWhenPatchingAssets(): void
    {
        $this->http->addResponse($this->fixtureResponse(__DIR__ . '/../fixtures/tag-list.json'));
        $this->http->addResponse($this->fixtureResponse(__DIR__ . '/../fixtures/asset-patch.json'));

        $this->client->patchAssetMetaData(new AssetPatch(
            id: 'whatever',
            tags: ['tag1'],
        ));

        $request = $this->http->getLastRequest();
        self::assertInstanceOf(RequestInterface::class, $request);

        $requestBody = (string) $request->getBody();
        self::assertJsonStringEqualsJsonString(
            json_encode(['tags' => ['f62b96a4-0755-47eb-ae3b-323427a7459c']], JSON_THROW_ON_ERROR),
            $requestBody,
            'The request body should contain the resolved id of the tag',
        );
    }

    public function testGetTags(): void
    {
        $this->http->addResponse($this->fixtureResponse(__DIR__ . '/../fixtures/tag-list.json'));
        $result = $this->client->getTags();

        $request = $this->http->getLastRequest();
        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame(
            RequestMethodInterface::METHOD_GET,
            $request->getMethod(),
            'Expected a GET request',
        );

        self::assertCount(2, $result);
        self::assertTrue($result->containsTagName('tag1'));
        self::assertTrue($result->containsTagName('tag2'));
    }

    public function testGetTagsWithUnexpectedResponse(): void
    {
        $this->http->addResponse($this->fixtureResponse(__DIR__ . '/../fixtures/not-found.json'));
        $this->expectException(UnexpectedResponse::class);
        $this->client->getTags();
    }

    public function testFetchSinglePageOfAssets(): void
    {
        $this->http->addResponse($this->fixtureResponse(__DIR__ . '/../fixtures/asset-list.json'));
        $this->http->addResponse($this->fixtureResponse(__DIR__ . '/../fixtures/empty-asset-list.json'));

        $list = $this->client->listAssets();

        $request = $this->http->getLastRequest();
        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame(
            RequestMethodInterface::METHOD_GET,
            $request->getMethod(),
            'Expected a GET request',
        );

        self::assertCount(6, $list);
    }

    public function testFetchMultiplePagesOfAssets(): void
    {
        $this->http->addResponse($this->fixtureResponse(__DIR__ . '/../fixtures/asset-list.json'));
        $this->http->addResponse($this->fixtureResponse(__DIR__ . '/../fixtures/asset-list.json'));
        $this->http->addResponse($this->fixtureResponse(__DIR__ . '/../fixtures/empty-asset-list.json'));

        $list = $this->client->listAssets();

        $requests = $this->http->getRequests();
        self::assertCount(3, $requests);
        $first = $requests[0];
        $second = $requests[1];

        self::assertSame('', $first->getUri()->getQuery());
        self::assertStringContainsString('cursor=eyJjcmVhdGVkQXQiOjE3MzkzNzcyMzI1NTd9', $second->getUri()->getQuery());

        self::assertCount(12, $list);
    }

    public function testListAssetsWithUnexpectedResponse(): void
    {
        $this->http->addResponse($this->fixtureResponse(__DIR__ . '/../fixtures/not-found.json'));
        $this->expectException(UnexpectedResponse::class);
        $this->client->listAssets();
    }

    public function testDeleteAsset(): void
    {
        $this->http->addResponse(new Response());
        $this->client->deleteAsset('whatever');

        $request = $this->http->getLastRequest();
        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame(
            RequestMethodInterface::METHOD_DELETE,
            $request->getMethod(),
            'Expected a DELETE request',
        );
        self::assertStringEndsWith(
            '/assets/whatever',
            (string) $request->getUri(),
            'The uri should end with the asset id',
        );
    }

    public function testPatchUnknownAsset(): void
    {
        $response = $this->fixtureResponse(__DIR__ . '/../fixtures/not-found.json')
            ->withStatus(404);
        $this->http->addResponse($response);

        try {
            $this->client->patchAssetMetaData(new AssetPatch(
                'whatever',
                'foo',
            ));
            self::fail('No exception was thrown');
        } catch (RequestFailure $e) {
            self::assertSame($response, $e->response);
            $last = $this->http->getLastRequest();
            self::assertInstanceOf(RequestInterface::class, $last);
            self::assertSame($last, $e->request);
        }
    }

    public function testPatchAssetWithUnexpectedResponse(): void
    {
        $this->http->addResponse($this->fixtureResponse(__DIR__ . '/../fixtures/not-found.json'));
        $this->expectException(UnexpectedResponse::class);
        $this->client->patchAssetMetaData(new AssetPatch(
            'whatever',
            'foo',
        ));
    }

    public function testCreateTag(): void
    {
        $this->http->addResponse($this->fixtureResponse(__DIR__ . '/../fixtures/create-tag.json'));

        $tag = $this->client->createTag('foo');

        self::assertSame('tag2', $tag->name);

        $request = $this->http->getLastRequest();
        self::assertInstanceOf(RequestInterface::class, $request);

        self::assertSame(RequestMethodInterface::METHOD_POST, $request->getMethod());
        self::assertStringEndsWith('/tags', (string) $request->getUri());
    }

    public function testCreateTagWithUnexpectedResponse(): void
    {
        $this->http->addResponse($this->fixtureResponse(__DIR__ . '/../fixtures/not-found.json'));
        $this->expectException(UnexpectedResponse::class);
        $this->client->createTag('foo');
    }

    public function testUploadAsset(): void
    {
        $this->http->addResponse($this->fixtureResponse(__DIR__ . '/../fixtures/asset-patch.json'));
        $this->http->addResponse($this->fixtureResponse(__DIR__ . '/../fixtures/tag-list.json'));
        $this->http->addResponse($this->fixtureResponse(__DIR__ . '/../fixtures/asset-patch.json'));
        $asset = $this->client->uploadAsset(
            'FILE CONTENT',
            'filename.txt',
            'foobar/dingdong',
            'NOTE CONTENT',
            'CREDIT CONTENT',
            'ALT CONTENT',
            ['tag1', 'tag2'],
        );

        self::assertSame('Z6zKUJbqstJ9-iXW', $asset->id);

        $requests = $this->http->getRequests();
        self::assertCount(3, $requests);
        $first = $requests[0];

        self::assertSame(RequestMethodInterface::METHOD_POST, $first->getMethod());
        $header = $first->getHeaderLine('Content-Type');
        self::assertStringStartsWith('multipart/form-data; charset=utf-8; boundary=', $header);

        $body = (string) $first->getBody();

        $expect = [
            'Content-Disposition: form-data; name="notes"',
            'Content-Disposition: form-data; name="credits"',
            'Content-Disposition: form-data; name="alt"',
            'name="file"; filename="filename.txt"',
            'FILE CONTENT',
            'foobar/dingdong',
            'NOTE CONTENT',
            'CREDIT CONTENT',
            'ALT CONTENT',
        ];

        foreach ($expect as $string) {
            self::assertStringContainsString($string, $body);
        }

        $request = $requests[2];
        $requestBody = (string) $request->getBody();
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'tags' => [
                    'f62b96a4-0755-47eb-ae3b-323427a7459c',
                    '4030d85a-d080-4562-a3f6-a852ebcf5e9a',
                ],
            ], JSON_THROW_ON_ERROR),
            $requestBody,
            'The request body should contain the resolved id of the tag',
        );
    }

    public function testUploadExcludesNullAssetProperties(): void
    {
        $this->http->addResponse($this->fixtureResponse(__DIR__ . '/../fixtures/asset-patch.json'));
        $this->client->uploadAsset(
            'FILE CONTENT',
            'filename.txt',
            'foobar/dingdong',
        );

        $requests = $this->http->getRequests();
        self::assertCount(1, $requests);
        $request = $requests[0];

        $body = (string) $request->getBody();

        $expect = [
            'Content-Disposition: form-data; name="notes"',
            'Content-Disposition: form-data; name="credits"',
            'Content-Disposition: form-data; name="alt"',
        ];

        foreach ($expect as $string) {
            self::assertStringNotContainsString($string, $body);
        }
    }

    public function testUploadWithUnexpectedResponse(): void
    {
        $this->http->addResponse($this->fixtureResponse(__DIR__ . '/../fixtures/not-found.json'));
        $this->expectException(UnexpectedResponse::class);
        $this->client->uploadAsset(
            'FILE CONTENT',
            'filename.txt',
            'foobar/dingdong',
        );
    }

    public function testPsrCommunicationFailuresAreWrapped(): void
    {
        $this->http->addException(new NetworkException('Foo', new Request()));
        $this->expectException(CommunicationFailure::class);
        $this->client->getTags();
    }
}
