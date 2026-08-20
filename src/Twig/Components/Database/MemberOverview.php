<?php

declare(strict_types=1);

namespace App\Twig\Components\Database;

use App\Entity\Database\Enums\MemberFilter;
use App\Entity\Database\Member;
use App\Repository\Database\MemberRepository;
use App\Twig\Components\Application\AbstractPaginatedOverview;
use App\Twig\Components\Concerns\FilterPillsTrait;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Override;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * The member register, filtered the way a secretary looks for someone: by what they typed, and by the state the
 * record is in.
 *
 * The counts on the chips are of the current search rather than of the whole table, so narrowing the search says how
 * many of those matches are expired rather than how many members are.
 *
 * @extends AbstractPaginatedOverview<Member>
 */
#[AsLiveComponent(
    name: 'Member:MemberOverview',
    template: 'components/Member/MemberOverview.html.twig',
)]
final class MemberOverview extends AbstractPaginatedOverview
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
    public string $filter = MemberFilter::Everyone->value;

    /** @var array<string, int>|null */
    private ?array $counts = null;

    public function __construct(private readonly MemberRepository $memberRepository)
    {
    }

    public function onFilterUpdated(): void
    {
        $this->counts = null;
        $this->resetToFirstPage();
    }

    /**
     * @return list<Member>
     */
    public function getMembers(): array
    {
        return $this->getRows();
    }

    /**
     * @return array<string, int>
     */
    public function getCounts(): array
    {
        return $this->counts ??= $this->memberRepository->countsForOverview($this->search);
    }

    /**
     * @return MemberFilter[]
     */
    public function getFilters(): array
    {
        return MemberFilter::cases();
    }

    /**
     * @return Paginator<Member>
     */
    #[Override]
    protected function createPaginator(
        int $page,
        int $pageSize,
    ): Paginator {
        return $this->memberRepository->paginateForOverview(
            search: $this->search,
            filter: MemberFilter::tryFrom($this->filter) ?? MemberFilter::Everyone,
            page: $page,
            pageSize: $pageSize,
        );
    }
}
