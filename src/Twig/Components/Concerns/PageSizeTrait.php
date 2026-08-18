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
    public const array PAGE_SIZES = [25, 50, 100, 250];

    #[LiveProp(
        writable: true,
        url: true,
        onUpdated: 'onPageSizeUpdated',
    )]
    public int $pageSize = 25;

    public function onPageSizeUpdated(): void
    {
        $this->pageSize = $this->pageSize();
        $this->page = 1;
    }

    protected function pageSize(): int
    {
        return in_array($this->pageSize, self::PAGE_SIZES, true) ? $this->pageSize : 25;
    }
}
