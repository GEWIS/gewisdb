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
 * The hourly mailing list jobs, kept apart from the rest of the housekeeping because they are the only ones that
 * reach out to Mailman and listmonk.
 *
 * Both jobs are dispatched at most once per hour no matter what the container does in between:
 *  - stateful(), so the checkpoint of what was last dispatched outlives the process. An in-memory checkpoint starts
 *    at "now" on every boot, which means a container coming back up cannot tell an hour it already handled from one
 *    it still owes, and a restart landing on the wrong side of :05 or :50 fires that hour a second time.
 *  - lock(), so only one worker advances that checkpoint. During a rolling deploy the old and the new container are
 *    both consuming for a moment, and without the lock they would each dispatch the same hourly run.
 */
#[AsSchedule('mailinglist')]
final readonly class MailingListSchedule implements ScheduleProviderInterface
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
        return (new Schedule())
            ->add(
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
            ->lock($this->lockFactory->createLock('scheduler-mailinglist'));
    }
}
