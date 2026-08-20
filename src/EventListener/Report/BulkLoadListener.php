<?php

declare(strict_types=1);

namespace App\EventListener\Report;

use App\Service\Report\ProjectionState;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

use function in_array;

/**
 * Keeps the projection out of the way while the database is being filled in bulk.
 *
 * `DatabaseUpdateListener` projects a decision the moment it is persisted, which is right for the application, where
 * a decision is recorded on its own. Loading fixtures persists a whole register in one flush, and the order Doctrine
 * chooses within that flush is its own business — a decision can reach the projection before the subdecisions it is
 * assembled from, and what gets written is a decision that reads as nothing.
 *
 * Nothing is lost by standing down: `report:generate:full` rebuilds the projection from the finished ledger, and
 * `make seed` runs it immediately after the fixtures for exactly that reason.
 */
#[AsEventListener(event: ConsoleEvents::COMMAND)]
final class BulkLoadListener
{
    private const array BULK_COMMANDS = [
        'doctrine:fixtures:load',
    ];

    public function __construct(private readonly ProjectionState $state)
    {
    }

    public function __invoke(ConsoleCommandEvent $event): void
    {
        if (
            !in_array(
                $event->getCommand()?->getName(),
                self::BULK_COMMANDS,
                true,
            )
        ) {
            return;
        }

        $this->state->disable();
    }
}
