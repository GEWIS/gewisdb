<?php

declare(strict_types=1);

namespace App\Tests\Scheduler;

use App\Scheduler\MaintenanceSchedule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;
use Symfony\Component\Scheduler\Generator\MessageContext;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\Trigger\CronExpressionTrigger;
use DateTimeImmutable;

use function sort;

/**
 * The housekeeping that used to be a crontab. Nothing runs it by hand, so what it contains and how it survives a
 * restart are only visible here.
 */
#[CoversClass(MaintenanceSchedule::class)]
class MaintenanceScheduleTest extends TestCase
{
    public function testRunsTheHousekeepingTheCrontabUsedTo(): void
    {
        $commands = [];

        foreach ($this->schedule()->getRecurringMessages() as $recurringMessage) {
            foreach ($recurringMessage->getMessages($this->context($recurringMessage)) as $message) {
                self::assertInstanceOf(RunCommandMessage::class, $message);

                $commands[] = (string) $message;
            }
        }

        sort($commands);

        self::assertSame(
            [
                'check:membership:renewal:graduate',
                'database:mailinglist:fetch all',
                'database:mailinglist:maintenance -f -vv',
                'database:mailinglist:sync-membership -f -vv all',
                'database:prospective-members:delete-expired',
                'report:generate:full',
            ],
            $commands,
        );
    }

    /**
     * A checkpoint that only lives in the process starts at "now" on every boot, so a container coming back up
     * cannot tell an hour it has already handled from one it still owes.
     */
    public function testKeepsItsCheckpointOutsideTheProcess(): void
    {
        self::assertNotNull($this->schedule()->getState());
    }

    /**
     * Two containers overlap during a rolling deploy, and without the lock both would dispatch the same run.
     */
    public function testLetsOnlyOneWorkerAdvanceTheSchedule(): void
    {
        self::assertNotNull($this->schedule()->getLock());
    }

    /**
     * Every job recomputes what the run before it would have done, so coming back from an outage catches up once
     * instead of replaying every hour that was missed.
     */
    public function testCatchesUpOnceAfterAnOutage(): void
    {
        self::assertTrue($this->schedule()->shouldProcessOnlyLastMissedRun());
    }

    /**
     * The rebuild of ReportDB runs at night, because it is the heaviest thing here.
     */
    public function testRebuildsTheProjectionNightly(): void
    {
        foreach ($this->schedule()->getRecurringMessages() as $recurringMessage) {
            foreach ($recurringMessage->getMessages($this->context($recurringMessage)) as $message) {
                self::assertInstanceOf(RunCommandMessage::class, $message);

                if ('report:generate:full' !== (string) $message) {
                    continue;
                }

                $trigger = $recurringMessage->getTrigger();

                self::assertInstanceOf(CronExpressionTrigger::class, $trigger);
                self::assertSame('0 1 * * *', (string) $trigger);

                return;
            }
        }

        self::fail('the projection is never rebuilt');
    }

    /**
     * What the generator would hand a message provider when a run comes due.
     */
    private function context(RecurringMessage $recurringMessage): MessageContext
    {
        return new MessageContext(
            'maintenance',
            $recurringMessage->getId(),
            $recurringMessage->getTrigger(),
            new DateTimeImmutable('2026-08-20 01:00:00'),
        );
    }

    private function schedule(): Schedule
    {
        return new MaintenanceSchedule(
            new ArrayAdapter(),
            new LockFactory(new InMemoryStore()),
        )->getSchedule();
    }
}
