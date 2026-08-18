<?php

declare(strict_types=1);

namespace App\Twig\Components\Database;

use App\Entity\Database\Enums\MeetingTypes;
use App\Entity\Database\Meeting;
use App\Repository\Database\MeetingRepository;
use App\Twig\Components\Application\AbstractPaginatedOverview;
use App\Twig\Components\Concerns\FilterPillsTrait;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Override;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * Every meeting that has been minuted, most recent first.
 *
 * How many decisions each holds is counted for the visible page in one query rather than read off each meeting, so
 * the table costs two queries whatever the page size.
 *
 * @extends AbstractPaginatedOverview<Meeting>
 */
#[AsLiveComponent(
    name: 'Decision:MeetingOverview',
    template: 'components/Decision/MeetingOverview.html.twig',
)]
final class MeetingOverview extends AbstractPaginatedOverview
{
    use FilterPillsTrait;

    /** Empty is every type, which is what the first chip stands for. */
    #[LiveProp(
        writable: true,
        url: true,
        onUpdated: 'onFilterUpdated',
    )]
    public string $filter = '';

    /** @var array<string, int>|null */
    private ?array $decisionCounts = null;

    /** @var array<string, int>|null */
    private ?array $counts = null;

    public function __construct(private readonly MeetingRepository $meetingRepository)
    {
    }

    public function onFilterUpdated(): void
    {
        $this->resetToFirstPage();
    }

    /**
     * @return MeetingTypes[]
     */
    public function getFilters(): array
    {
        return MeetingTypes::cases();
    }

    /**
     * @return array<string, int>
     */
    public function getCounts(): array
    {
        return $this->counts ??= $this->meetingRepository->countsByType();
    }

    /**
     * @return list<Meeting>
     */
    public function getMeetings(): array
    {
        return $this->getRows();
    }

    /**
     * @return array<string, int>
     */
    public function getDecisionCounts(): array
    {
        return $this->decisionCounts ??= $this->meetingRepository->decisionCountsFor($this->getMeetings());
    }

    /**
     * @return Paginator<Meeting>
     */
    #[Override]
    protected function createPaginator(
        int $page,
        int $pageSize,
    ): Paginator {
        return $this->meetingRepository->paginateForOverview(
            $page,
            $pageSize,
            MeetingTypes::tryFrom($this->filter),
        );
    }
}
