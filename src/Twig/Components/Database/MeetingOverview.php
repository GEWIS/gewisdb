<?php

declare(strict_types=1);

namespace App\Twig\Components\Database;

use App\Entity\Database\Meeting;
use App\Repository\Database\MeetingRepository;
use App\Twig\Components\Application\AbstractPaginatedOverview;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Override;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

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
    /** @var array<string, int>|null */
    private ?array $decisionCounts = null;

    public function __construct(private readonly MeetingRepository $meetingRepository)
    {
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
        return $this->meetingRepository->paginateForOverview($page, $pageSize);
    }
}
