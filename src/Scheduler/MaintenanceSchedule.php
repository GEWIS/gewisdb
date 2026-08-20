<?php

declare(strict_types=1);

namespace App\Scheduler;

use Override;
use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * The housekeeping that used to sit in the container crontab: the nightly jobs, the half-hourly membership check, and
 * the hourly round trip to Mailman and listmonk. They are one schedule because they are one kind of work — keeping the
 * register and what hangs off it current — and because the mailing lists are already maintained from here.
 *
 * The whole schedule is dispatched at most once per due time no matter what the container does in between:
 *  - stateful(), so the checkpoint of what was last dispatched outlives the process. An in-memory checkpoint starts at
 *    "now" on every boot, which means a container coming back up cannot tell an hour it already handled from one it
 *    still owes, and a restart landing on the wrong side of :05 or :50 fires that hour a second time.
 *  - processOnlyLastMissedRun(), so coming back from a longer outage catches up once rather than replaying every hour
 *    that was missed. Every job here recomputes whatever the run before it would have done, so the last one is the
 *    only one worth having.
 *  - lock(), so only one worker advances that checkpoint. During a rolling deploy the old and the new container are
 *    both consuming for a moment, and without the lock they would each dispatch the same run.
 */
#[AsSchedule('maintenance')]
final readonly class MaintenanceSchedule implements ScheduleProviderInterface
{
    public function __construct(
        #[Autowire(service: 'cache.app')]
        private CacheInterface $cache,
        private LockFactory $lockFactory,
    ) {
    }

    #[Override]
    public function getSchedule(): Schedule
    {
        return new Schedule()
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
                RecurringMessage::cron(
                    '50 * * * *',
                    new RunCommandMessage('database:mailinglist:fetch all'),
                ),
                RecurringMessage::cron(
                    '5 * * * *',
                    new RunCommandMessage('database:mailinglist:sync-membership -f -vv all'),
                ),
            )
            ->stateful($this->cache)
            ->processOnlyLastMissedRun(true)
            ->lock($this->lockFactory->createLock('scheduler-maintenance'));
    }
}
