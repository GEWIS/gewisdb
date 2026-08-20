<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Twig\Components\Application\AbstractPaginatedOverview;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Override;

/**
 * A concrete overview that pages over nothing, so the paging itself can be looked at on its own.
 *
 * @extends AbstractPaginatedOverview<object>
 */
final class PaginatedOverviewDouble extends AbstractPaginatedOverview
{
    /** How often the query was built, which is what says whether a page was reused. */
    public int $queries = 0;

    /** @var array{int, int}|null The page and page size the last query was built for. */
    public ?array $askedFor = null;

    /**
     * @param Paginator<object> $paginator
     */
    public function __construct(private readonly Paginator $paginator)
    {
    }

    /**
     * @return Paginator<object>
     */
    #[Override]
    protected function createPaginator(
        int $page,
        int $pageSize,
    ): Paginator {
        $this->queries++;
        $this->askedFor = [
            $page,
            $pageSize,
        ];

        return $this->paginator;
    }
}
