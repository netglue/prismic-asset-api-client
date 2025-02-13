<?php

declare(strict_types=1);

namespace Prismic\Asset\Model;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

use function count;
use function strtolower;

/**
 * @implements IteratorAggregate<int, AssetTag>
 * @psalm-api
 */
final readonly class TagCollection implements IteratorAggregate, Countable
{
    /** @param list<AssetTag> $tags */
    public function __construct(private array $tags)
    {
    }

    public function count(): int
    {
        return count($this->tags);
    }

    /** @return Traversable<int, AssetTag> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->tags);
    }

    public function containsTagName(string $name): bool
    {
        foreach ($this->tags as $tag) {
            if (strtolower($tag->name) === strtolower($name)) {
                return true;
            }
        }

        return false;
    }

    /** @param non-empty-string $name */
    public function findByName(string $name): AssetTag|null
    {
        foreach ($this->tags as $tag) {
            if (strtolower($tag->name) === strtolower($name)) {
                return $tag;
            }
        }

        return null;
    }
}
