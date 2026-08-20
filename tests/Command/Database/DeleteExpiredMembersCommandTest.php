<?php

declare(strict_types=1);

namespace App\Tests\Command\Database;

use App\Command\Database\DeleteExpiredMembersCommand;
use App\Command\Database\DeleteExpiredProspectiveMembersCommand;
use App\Service\Database\Member as MemberService;
use DateTime;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * The sweep that nobody watches. It deletes members outright, so the guards in front of it — the shape of the date,
 * and a confirmation that defaults to no — are the whole safety of the thing.
 */
#[CoversClass(DeleteExpiredMembersCommand::class)]
#[CoversClass(DeleteExpiredProspectiveMembersCommand::class)]
class DeleteExpiredMembersCommandTest extends TestCase
{
    public function testDeletesUpToTheConfirmedDate(): void
    {
        $memberService = $this->createMock(MemberService::class);
        $memberService->expects(self::once())
            ->method('removeExpiredMembers')
            ->with(self::callback(
                static fn (DateTime $expiration): bool => '2020-07-01' === $expiration->format('Y-m-d'),
            ));

        $tester = $this->tester($memberService);
        $tester->setInputs(['yes']);

        self::assertSame(Command::SUCCESS, $tester->execute(['--expiration' => '2020-07-01']));
    }

    /**
     * The confirmation defaults to no, so a run that is not answered deletes nothing.
     */
    #[DataProvider('answersThatAreNotYes')]
    public function testDeletesNothingWithoutAConfirmation(string $answer): void
    {
        $memberService = $this->createMock(MemberService::class);
        $memberService->expects(self::never())->method('removeExpiredMembers');

        $tester = $this->tester($memberService);
        $tester->setInputs([$answer]);

        self::assertSame(Command::SUCCESS, $tester->execute(['--expiration' => '2020-07-01']));
        self::assertStringContainsString('Not deleting expired members.', $tester->getDisplay());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function answersThatAreNotYes(): array
    {
        return [
            'no' => ['no'],
            'nothing at all' => [''],
        ];
    }

    /**
     * A date that is not one would otherwise be read by DateTime as something else entirely, and "yesterday" is a
     * perfectly good relative date to hand a deletion.
     */
    #[DataProvider('datesThatAreNotDates')]
    public function testRefusesAnExpirationThatIsNotADate(string $expiration): void
    {
        $memberService = $this->createMock(MemberService::class);
        $memberService->expects(self::never())->method('removeExpiredMembers');

        $this->expectException(InvalidArgumentException::class);

        $this->tester($memberService)->execute(['--expiration' => $expiration]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function datesThatAreNotDates(): array
    {
        return [
            'a relative date' => ['yesterday'],
            'the wrong order' => ['01-07-2020'],
            'a year alone' => ['2020'],
            'empty' => [''],
        ];
    }

    /**
     * Its sibling has nothing to confirm: what counts as expired is worked out from the checkout sessions rather
     * than given by whoever runs it.
     */
    public function testSweepsProspectiveMembersWithoutAsking(): void
    {
        $memberService = $this->createMock(MemberService::class);
        $memberService->expects(self::once())->method('removeExpiredProspectiveMembers');

        $tester = new CommandTester(new DeleteExpiredProspectiveMembersCommand($memberService));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
    }

    private function tester(MemberService $memberService): CommandTester
    {
        $command = new DeleteExpiredMembersCommand($memberService);
        // The confirmation is asked through the question helper, which an application would otherwise provide.
        $command->setHelperSet(new HelperSet([new QuestionHelper()]));

        return new CommandTester($command);
    }
}
