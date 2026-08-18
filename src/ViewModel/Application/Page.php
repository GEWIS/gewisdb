<?php

declare(strict_types=1);

namespace App\ViewModel\Application;

use Doctrine\ORM\Tools\Pagination\Paginator;
use IteratorAggregate;
use Traversable;

use function ceil;
use function count;
use function in_array;
use function max;
use function min;

/**
 * One page of a listing, with the totals the pagination partial renders.
 *
 * @template T of object
 *
 * @implements IteratorAggregate<array-key, T>
 */
final readonly class Page implements IteratorAggregate
{
    public const array PAGE_SIZES = [25, 50, 100, 250];

    public const int DEFAULT_PAGE_SIZE = 25;

    /**
     * @param Paginator<T> $paginator
     */
    private function __construct(
        private Paginator $paginator,
        public int $page,
        public int $pageSize,
        public int $totalCount,
        public int $totalPages,
    ) {
    }

    /**
     * @param Paginator<T> $paginator
     *
     * @return self<T>
     */
    public static function of(
        Paginator $paginator,
        int $page,
        int $pageSize,
    ): self {
        $totalCount = count($paginator);
        $totalPages = max(1, (int) ceil($totalCount / $pageSize));

        return new self($paginator, min($page, $totalPages), $pageSize, $totalCount, $totalPages);
    }

    /**
     * Both come straight off the query string, so neither can be trusted: an out-of-range page would run an
     * unbounded OFFSET and an arbitrary size would let one request ask for the whole table.
     */
    public static function clampPage(int $page): int
    {
        return max(1, $page);
    }

    public static function clampPageSize(int $pageSize): int
    {
        return in_array($pageSize, self::PAGE_SIZES, true) ? $pageSize : self::DEFAULT_PAGE_SIZE;
    }

    /**
     * @return Traversable<array-key, T>
     */
    public function getIterator(): Traversable
    {
        return $this->paginator->getIterator();
    }
}
