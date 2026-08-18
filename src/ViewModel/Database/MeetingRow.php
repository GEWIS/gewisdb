<?php

declare(strict_types=1);

namespace App\ViewModel\Database;

use App\Entity\Database\Meeting;

/**
 * One meeting in the overview, with the number of decisions taken in it.
 *
 * The count comes from the query rather than from the meeting itself, so that listing every meeting does not load
 * every decision ever taken.
 */
final readonly class MeetingRow
{
    public function __construct(
        public Meeting $meeting,
        public int $decisionCount,
    ) {
    }
}
