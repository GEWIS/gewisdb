<?php

declare(strict_types=1);

namespace App\Scheduler;

use Override;
use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

/**
 * The nightly housekeeping that used to sit in the container crontab.
 *
 * Everything here only ever touches our own database, and each job recomputes whatever the previous run would have
 * done. That makes the schedule safe to leave stateless: a run missed because the container was down is skipped
 * rather than replayed on boot, which is what the crontab did as well.
 */
#[AsSchedule('maintenance')]
final readonly class MaintenanceSchedule implements ScheduleProviderInterface
{
    public function __construct(private LockFactory $lockFactory)
    {
    }

    #[Override]
    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(
                RecurringMessage::cron(
                    '0 1 * * *',
                    new RunCommandMessage('report:generate:full'),
                ),
                RecurringMessage::cron(
                    '*/30 * * * *',
                    new RunCommandMessage('check:membership:renewal:graduate'),
                ),
                RecurringMessage::cron(
                    '0 2 * * *',
                    new RunCommandMessage('database:prospective-members:delete-expired'),
                ),
                RecurringMessage::cron(
                    '40 2 * * *',
                    new RunCommandMessage('database:mailinglist:maintenance -f -vv'),
                ),
            )
            // A full ReportDB rebuild running twice at once would have the two runs writing over each other, so even
            // without persisted state the schedule is held by a lock.
            ->lock($this->lockFactory->createLock('scheduler-maintenance'));
    }
}
