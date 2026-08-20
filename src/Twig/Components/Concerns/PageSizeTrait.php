<?php

declare(strict_types=1);

namespace App\Twig\Components\Concerns;

use Symfony\UX\LiveComponent\Attribute\LiveProp;

use function in_array;

/**
 * Read the size through {@see pageSize()} rather than the property: the prop is client-writable, so an arbitrary
 * value would otherwise reach the query and let one request ask for the whole table.
 */
trait PageSizeTrait
{
    public const array PAGE_SIZES = [
        10,
        25,
        50,
        100,
    ];

    #[LiveProp(
        writable: true,
        url: true,
        onUpdated: 'onPageSizeUpdated',
    )]
    public int $pageSize = 10;

    public function onPageSizeUpdated(): void
    {
        $this->pageSize = $this->pageSize();
        $this->page = 1;
    }

    /**
     * The sizes offered, so the pagination partial does not keep its own copy of the list.
     *
     * @return int[]
     */
    public function getPageSizes(): array
    {
        return self::PAGE_SIZES;
    }

    protected function pageSize(): int
    {
        return in_array(
            $this->pageSize,
            self::PAGE_SIZES,
            true,
        )
            ? $this->pageSize
            : self::PAGE_SIZES[0];
    }
}
