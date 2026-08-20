<?php

declare(strict_types=1);

namespace App\Tests\Twig\Components\Application;

use App\Tests\Support\PaginatedOverviewDouble;
use App\Twig\Components\Application\AbstractPaginatedOverview;
use App\Twig\Components\Concerns\PageSizeTrait;
use ArrayIterator;
use Doctrine\ORM\Tools\Pagination\Paginator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractPaginatedOverview::class)]
#[CoversClass(PageSizeTrait::class)]
class AbstractPaginatedOverviewTest extends TestCase
{
    /**
     * Both props are writable from the browser, so what reaches the query is the clamped value and never the one
     * that was asked for: an arbitrary page size would otherwise let one request ask for the whole table.
     */
    #[DataProvider('pageSizesAndWhatIsQueried')]
    public function testClampsThePageSizeToTheOnesOnOffer(
        int $pageSize,
        int $queried,
    ): void {
        $overview = $this->overview();
        $overview->pageSize = $pageSize;

        $overview->getRows();

        self::assertSame([1, $queried], $overview->askedFor);
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function pageSizesAndWhatIsQueried(): array
    {
        return [
            'one that is offered' => [50, 50],
            'the whole table' => [100000, 10],
            'nothing at all' => [0, 10],
            'a negative size' => [-25, 10],
            'between two of the offered ones' => [26, 10],
        ];
    }

    #[DataProvider('pagesAndWhatIsQueried')]
    public function testNeverQueriesBeforeTheFirstPage(
        int $page,
        int $queried,
    ): void {
        $overview = $this->overview();
        $overview->page = $page;

        $overview->getRows();

        self::assertSame([$queried, 10], $overview->askedFor);
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function pagesAndWhatIsQueried(): array
    {
        return [
            'a real page' => [3, 3],
            'before the first' => [0, 1],
            'a negative page' => [-5, 1],
        ];
    }

    /**
     * Changing the page size moves everything, so the reader goes back to the start rather than to whatever now
     * happens to be under the old page number.
     */
    public function testStartsOverWhenThePageSizeChanges(): void
    {
        $overview = $this->overview();
        $overview->page = 4;
        $overview->pageSize = 999;

        $overview->onPageSizeUpdated();

        self::assertSame(10, $overview->pageSize);
        self::assertSame(1, $overview->page);
        self::assertSame(PaginatedOverviewDouble::PAGE_SIZES, $overview->getPageSizes());
    }

    #[DataProvider('totalsAndPages')]
    public function testCountsThePagesOnTheClampedSize(
        int $total,
        int $pageSize,
        int $pages,
    ): void {
        $overview = $this->overview($total);
        $overview->pageSize = $pageSize;

        self::assertSame($pages, $overview->getTotalPages());
    }

    /**
     * @return array<string, array{int, int, int}>
     */
    public static function totalsAndPages(): array
    {
        return [
            'an exact fit' => [50, 25, 2],
            'a partial last page' => [51, 25, 3],
            // A reader is always on page 1 of 1, even of nothing.
            'nothing to show' => [0, 25, 1],
            'a size that is not on offer falls back to the smallest' => [30, 999, 3],
        ];
    }

    #[DataProvider('requestedPagesAndWhereTheyLand')]
    public function testGoingToAPageStaysWithinTheOnesThatExist(
        int $requested,
        int $lands,
    ): void {
        $overview = $this->overview(45);
        $overview->pageSize = 10;

        $overview->gotoPage($requested);

        self::assertSame($lands, $overview->page);
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function requestedPagesAndWhereTheyLand(): array
    {
        return [
            'one that exists' => [3, 3],
            'past the end' => [99, 5],
            'before the beginning' => [0, 1],
        ];
    }

    /**
     * One query per request: the rows, the total and the page count all read the same result.
     */
    public function testQueriesOnceForEverythingOnAPage(): void
    {
        $overview = $this->overview(45);

        $overview->getRows();
        $overview->getTotalCount();
        $overview->getTotalPages();

        self::assertSame(1, $overview->queries);
    }

    public function testQueriesAgainOnceTheReaderMovesOn(): void
    {
        $overview = $this->overview(45);

        $overview->getRows();
        $overview->page = 2;
        $overview->getRows();

        self::assertSame(2, $overview->queries);
        self::assertSame([2, 10], $overview->askedFor);
    }

    private function overview(int $total = 0): PaginatedOverviewDouble
    {
        $paginator = self::createStub(Paginator::class);
        $paginator->method('count')->willReturn($total);
        $paginator->method('getIterator')->willReturn(new ArrayIterator([]));

        return new PaginatedOverviewDouble($paginator);
    }
}
