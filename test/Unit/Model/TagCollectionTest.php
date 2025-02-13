<?php

declare(strict_types=1);

namespace Prismic\Asset\Test\Unit\Model;

use CuyZ\Valinor\Mapper\Source\JsonSource;
use CuyZ\Valinor\MapperBuilder;
use PHPUnit\Framework\TestCase;
use Prismic\Asset\Model\AssetTag;
use Prismic\Asset\Model\TagCollection;

use function file_get_contents;
use function iterator_to_array;

class TagCollectionTest extends TestCase
{
    private TagCollection $collection;

    protected function setUp(): void
    {
        $fixture = file_get_contents(__DIR__ . '/../../fixtures/tag-list.json');
        self::assertIsString($fixture);

        $this->collection = new TagCollection(
            (new MapperBuilder())
                ->allowPermissiveTypes()
                ->enableFlexibleCasting()
                ->mapper()
                ->map(
                    'array{items: list<' . AssetTag::class . '>}',
                    new JsonSource($fixture),
                )['items'],
        );
    }

    public function testCollectionsAreCountable(): void
    {
        self::assertCount(2, $this->collection);
    }

    public function testContainsTagName(): void
    {
        self::assertTrue($this->collection->containsTagName('tag1'));
        self::assertFalse($this->collection->containsTagName('foo'));
    }

    public function testGetTag(): void
    {
        self::assertNull($this->collection->findByName('foo'));
        self::assertNotNull($this->collection->findByName('tag1'));
    }

    public function testIsIterator(): void
    {
        $array = iterator_to_array($this->collection, false);

        self::assertCount(2, $array);
        self::assertContainsOnlyInstancesOf(AssetTag::class, $array);
    }
}
