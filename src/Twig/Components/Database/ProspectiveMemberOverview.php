<?php

declare(strict_types=1);

namespace App\Twig\Components\Database;

use App\Entity\Database\Enums\ProspectiveMemberFilter;
use App\Entity\Database\ProspectiveMember;
use App\Repository\Database\ProspectiveMemberRepository;
use App\Twig\Components\Application\AbstractPaginatedOverview;
use App\Twig\Components\Concerns\FilterPillsTrait;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Override;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * Everyone who has registered but is not a member yet, in the order they will be dealt with.
 *
 * @extends AbstractPaginatedOverview<ProspectiveMember>
 */
#[AsLiveComponent(
    name: 'Join:ProspectiveMemberOverview',
    template: 'components/Join/ProspectiveMemberOverview.html.twig',
)]
final class ProspectiveMemberOverview extends AbstractPaginatedOverview
{
    use FilterPillsTrait;

    #[LiveProp(
        writable: true,
        url: true,
        onUpdated: 'onFilterUpdated',
    )]
    public string $search = '';

    #[LiveProp(
        writable: true,
        url: true,
        onUpdated: 'onFilterUpdated',
    )]
    public string $filter = ProspectiveMemberFilter::All->value;

    /** @var array<string, int>|null */
    private ?array $counts = null;

    public function __construct(private readonly ProspectiveMemberRepository $prospectiveMemberRepository)
    {
    }

    public function onFilterUpdated(): void
    {
        $this->counts = null;
        $this->resetToFirstPage();
    }

    /**
     * @return list<ProspectiveMember>
     */
    public function getProspectiveMembers(): array
    {
        return $this->getRows();
    }

    /**
     * @return array<string, int>
     */
    public function getCounts(): array
    {
        return $this->counts ??= $this->prospectiveMemberRepository->countsForOverview($this->search);
    }

    /**
     * @return ProspectiveMemberFilter[]
     */
    public function getFilters(): array
    {
        return ProspectiveMemberFilter::cases();
    }

    /**
     * @return Paginator<ProspectiveMember>
     */
    #[Override]
    protected function createPaginator(
        int $page,
        int $pageSize,
    ): Paginator {
        return $this->prospectiveMemberRepository->paginateForOverview(
            search: $this->search,
            filter: ProspectiveMemberFilter::tryFrom($this->filter) ?? ProspectiveMemberFilter::All,
            page: $page,
            pageSize: $pageSize,
        );
    }
}
