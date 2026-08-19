<?php

declare(strict_types=1);

namespace App\Service\Report;

/**
 * Whether ReportDB is following the ledger, and whether it is in the middle of doing so.
 *
 * Shared by the listeners that keep the projection level with the ledger. The container guarantees they all hold the
 * same instance, which is what lets one of them stand the others down.
 */
final class ProjectionState
{
    private bool $enabled = true;

    private bool $projecting = false;

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Stop following the ledger. {@see \App\EventListener\Report\BulkLoadListener} does this for a bulk load, after
     * which the projection is rebuilt in one pass instead.
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Whether a projection is already under way.
     *
     * Generating into ReportDB flushes it, and that flush reaches the update listener again through the entities it
     * just wrote; this is what stops the recursion.
     */
    public function isProjecting(): bool
    {
        return $this->projecting;
    }

    public function beginProjecting(): void
    {
        $this->projecting = true;
    }

    public function endProjecting(): void
    {
        $this->projecting = false;
    }
}
