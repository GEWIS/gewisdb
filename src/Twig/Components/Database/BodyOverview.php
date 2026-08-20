<?php

declare(strict_types=1);

namespace App\Twig\Components\Database;

use App\Entity\Database\SubDecision\Foundation;
use App\Repository\Database\SubDecision\FoundationRepository;
use App\Twig\Components\Application\AbstractPaginatedOverview;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Override;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * The committees and fraternities that exist, searched by abbreviation or name.
 *
 * A body is not an entity of its own: it is the Foundation subdecision that created it, so this pages over those.
 *
 * @extends AbstractPaginatedOverview<Foundation>
 */
#[AsLiveComponent(
    name: 'Database:BodyOverview',
    template: 'components/Database/BodyOverview.html.twig',
)]
final class BodyOverview extends AbstractPaginatedOverview
{
    #[LiveProp(
        writable: true,
        url: true,
        onUpdated: 'onSearchUpdated',
    )]
    public string $search = '';

    public function __construct(private readonly FoundationRepository $foundationRepository)
    {
    }

    public function onSearchUpdated(): void
    {
        $this->resetToFirstPage();
    }

    /**
     * @return list<Foundation>
     */
    public function getBodies(): array
    {
        return $this->getRows();
    }

    /**
     * @return Paginator<Foundation>
     */
    #[Override]
    protected function createPaginator(
        int $page,
        int $pageSize,
    ): Paginator {
        return $this->foundationRepository->paginateForOverview(
            $this->search,
            $page,
            $pageSize,
        );
    }
}
