<?php

declare(strict_types=1);

namespace App\ViewModel\Database;

use App\Entity\Database\Meeting;

/**
 * A meeting with the decisions taken in it.
 */
final readonly class MeetingView
{
    /**
     * @param DecisionRow[]   $decisions
     * @param array<int, int> $nextDecisionNumbers the first free decision number of every point that already has
     *                                             decisions, so that entering the next one does not need looking up.
     */
    public function __construct(
        public Meeting $meeting,
        public array $decisions,
        public array $nextDecisionNumbers,
    ) {
    }
}
