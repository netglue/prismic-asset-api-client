<?php

declare(strict_types=1);

namespace Prismic\Asset;

use CuyZ\Valinor\Mapper\Source\JsonSource;
use CuyZ\Valinor\MapperBuilder;
use Fig\Http\Message\RequestMethodInterface;
use Override;
use Prismic\Asset\Exception\CommunicationFailure;
use Prismic\Asset\Exception\RequestFailure;
use Prismic\Asset\Exception\UnexpectedResponse;
use Prismic\Asset\Model\Asset;
use Prismic\Asset\Model\AssetListPage;
use Prismic\Asset\Model\AssetPatch;
use Prismic\Asset\Model\AssetTag;
use Prismic\Asset\Model\TagCollection;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use SensitiveParameter;
use Throwable;

use function array_merge;
use function http_build_query;
use function implode;
use function json_encode;
use function ltrim;
use function sprintf;

use const JSON_THROW_ON_ERROR;

final readonly class AssetClient implements Client
{
    /**
     * @param non-empty-string $token
     * @param non-empty-string $repository
     */
    public function __construct(
        #[SensitiveParameter]
        private string $token,
        private string $repository,
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private UriFactoryInterface $uriFactory,
        private StreamFactoryInterface $streamFactory,
        private string $baseUri = Client::DEFAULT_BASE_URI,
    ) {
    }

    #[Override]
    public function getTags(): TagCollection
    {
        $request = $this->createRequest(RequestMethodInterface::METHOD_GET, '/tags');
        $response = $this->sendRequest($request);

        try {
            $payload = (new MapperBuilder())
                ->allowPermissiveTypes()
                ->enableFlexibleCasting()
                ->mapper()
                ->map(
                    'array{items: list<' . AssetTag::class . '>}',
                    new JsonSource((string) $response->getBody()),
                );
        } catch (Throwable $e) {
            throw new UnexpectedResponse('Failed to parse response payload', 0, $e);
        }

        return new TagCollection($payload['items']);
    }

    /** @inheritDoc */
    #[Override]
    public function uploadAsset(
        string $fileContent,
        string $fileName,
        string $mimeType,
        string|null $notes = null,
        string|null $credits = null,
        string|null $alt = null,
        array $tags = [],
    ): Asset {
        $boundary = '--__X_Prismic-Asset-Boundary__--';

        $payload = [
            $boundary,
            sprintf('Content-Disposition: form-data; name="file"; filename="%s"', $fileName),
            sprintf('Content-Type: %s', $mimeType),
            $fileContent,
        ];

        $meta = [
            'notes' => $notes,
            'credits' => $credits,
            'alt' => $alt,
        ];

        foreach ($meta as $name => $value) {
            if ($value === null) {
                continue;
            }

            $payload[] = $boundary;
            $payload[] = sprintf('Content-Disposition: form-data; name="%s"', $name);
            $payload[] = $value;
        }

        $payload = implode("\r\n", $payload);

        $request = $this->createRequest(RequestMethodInterface::METHOD_POST, '/assets')
            ->withHeader('Content-Type', sprintf('multipart/form-data; charset=utf-8; boundary=%s', $boundary))
            ->withBody($this->streamFactory->createStream($payload));

        $response = $this->sendRequest($request);
        $body = (string) $response->getBody();

        try {
            $asset = (new MapperBuilder())
                ->allowSuperfluousKeys()
                ->allowPermissiveTypes()
                ->enableFlexibleCasting()
                ->mapper()
                ->map(
                    Asset::class,
                    new JsonSource($body),
                );
        } catch (Throwable $e) {
            throw new UnexpectedResponse('Failed to parse response payload', 0, $e);
        }

        if ($tags !== []) {
            $asset = $this->patchAssetMetaData(new AssetPatch(
                $asset->id,
                null,
                null,
                null,
                null,
                $tags,
            ));
        }

        return $asset;
    }

    #[Override]
    public function deleteAsset(string $id): void
    {
        $this->sendRequest($this->createRequest(
            RequestMethodInterface::METHOD_DELETE,
            sprintf('/assets/%s', $id),
        ));
    }

    #[Override]
    public function patchAssetMetaData(AssetPatch $patch): Asset
    {
        $payload = $patch->toArray();
        if (isset($payload['tags']) && $payload['tags'] !== []) {
            $payload['tags'] = $this->resolveTags($payload['tags']);
        }

        $request = $this->createRequest(
            RequestMethodInterface::METHOD_PATCH,
            sprintf('/assets/%s', $patch->id),
        )
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream(
                json_encode($payload, JSON_THROW_ON_ERROR),
            ));

        $response = $this->sendRequest($request);

        try {
            return (new MapperBuilder())
                ->allowSuperfluousKeys()
                ->allowPermissiveTypes()
                ->enableFlexibleCasting()
                ->mapper()
                ->map(
                    Asset::class,
                    new JsonSource((string) $response->getBody()),
                );
        } catch (Throwable $e) {
            throw new UnexpectedResponse('Failed to parse response payload', 0, $e);
        }
    }

    /**
     * @param non-empty-list<non-empty-string> $tags
     *
     * @return non-empty-list<non-empty-string>
     */
    private function resolveTags(array $tags): array
    {
        $known = $this->getTags();
        $list = [];
        foreach ($tags as $name) {
            $tag = $known->findByName($name);
            $tag ??= $this->createTag($name);
            $list[] = $tag->id;
        }

        return $list;
    }

    #[Override]
    public function createTag(string $tagName): AssetTag
    {
        $request = $this->createRequest(RequestMethodInterface::METHOD_POST, '/tags')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream(
                json_encode(['name' => $tagName], JSON_THROW_ON_ERROR),
            ));
        $response = $this->sendRequest($request);

        try {
            return (new MapperBuilder())
                ->allowSuperfluousKeys()
                ->enableFlexibleCasting()
                ->mapper()
                ->map(
                    AssetTag::class,
                    new JsonSource((string) $response->getBody()),
                );
        } catch (Throwable $e) {
            throw new UnexpectedResponse('Failed to parse response payload', 0, $e);
        }
    }

    /**
     * @param non-empty-string      $method
     * @param non-empty-string      $path
     * @param array<string, scalar> $query
     */
    private function createRequest(string $method, string $path, array $query = []): RequestInterface
    {
        $uri = $this->uriFactory->createUri(sprintf(
            '%s/%s',
            $this->baseUri,
            ltrim($path, '/'),
        ));

        if ($query !== []) {
            $uri = $uri->withQuery(http_build_query($query));
        }

        return $this->requestFactory->createRequest($method, $uri)
            ->withHeader('Authorization', sprintf('Bearer %s', $this->token))
            ->withHeader('repository', $this->repository);
    }

    private function sendRequest(RequestInterface $request): ResponseInterface
    {
        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $error) {
            throw CommunicationFailure::fromPsrError($request, $error);
        }

        $status = $response->getStatusCode();
        if ($status >= 400) {
            throw RequestFailure::fromExchange($request, $response);
        }

        return $response;
    }

    /** @inheritDoc */
    public function listAssets(): array
    {
        $page = $this->fetchAssetPage(null);
        $list = $results = $page->items;
        while ($results !== []) { // The final page will have an empty set
            $page = $this->fetchAssetPage($page->cursor);
            $results = $page->items;
            $list = array_merge($list, $page->items);
        }

        return $list;
    }

    private function fetchAssetPage(string|null $cursor): AssetListPage
    {
        $query = $cursor === null ? [] : ['cursor' => $cursor];
        $request = $this->createRequest(RequestMethodInterface::METHOD_GET, '/assets', $query);
        $response = $this->sendRequest($request);
        $body = (string) $response->getBody();

        try {
            return (new MapperBuilder())
                ->allowSuperfluousKeys()
                ->allowPermissiveTypes()
                ->enableFlexibleCasting()
                ->mapper()
                ->map(
                    AssetListPage::class,
                    new JsonSource($body),
                );
        } catch (Throwable $e) {
            throw new UnexpectedResponse('Failed to parse response payload', 0, $e);
        }
    }
}
