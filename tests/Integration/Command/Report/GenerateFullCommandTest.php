<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command\Report;

use App\Command\Report\GenerateFullCommand;
use App\Entity\Database\Enums\InstallationFunctions;
use App\Tests\Support\LedgerBuilder;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

use function implode;
use function sprintf;

/**
 * `report:generate:full` rebuilds ReportDB by replaying the ledger, and is what repairs the projection when it has
 * drifted. Two things have to hold for that to be a repair rather than a second kind of drift: replaying must not
 * change a projection that is already correct, and a projection rebuilt from nothing must come out the same as the
 * one the listeners wrote as the ledger was written.
 */
#[CoversClass(GenerateFullCommand::class)]
class GenerateFullCommandTest extends KernelTestCase
{
    private EntityManagerInterface $report;
    private LedgerBuilder $build;

    #[Override]
    protected function setUp(): void
    {
        self::bootKernel();

        $ledger = self::getContainer()->get(EntityManagerInterface::class);
        $report = self::getContainer()->get('doctrine')->getManager('report');
        self::assertInstanceOf(EntityManagerInterface::class, $report);

        $this->report = $report;
        $this->build = new LedgerBuilder($ledger);

        $this->aLedgerWorthProjecting();
    }

    public function testReplayingAProjectionThatIsAlreadyCorrectChangesNothing(): void
    {
        $before = $this->projectedRowCounts();

        $this->replay();

        self::assertSame($before, $this->projectedRowCounts());
    }

    /**
     * The listeners and the replay are two implementations of the same projection, and this is the only thing that
     * says they agree.
     */
    public function testAProjectionRebuiltFromNothingMatchesTheOneTheListenersWrote(): void
    {
        $listenersWrote = $this->projectedRowCounts();

        $this->emptyTheProjection();

        self::assertNotSame($listenersWrote, $this->projectedRowCounts(), 'the projection was not emptied');

        $this->replay();

        self::assertSame($listenersWrote, $this->projectedRowCounts());
    }

    /**
     * Enough of a ledger that the replay has every derived table to fill: an organ, someone in it, and a key.
     */
    private function aLedgerWorthProjecting(): void
    {
        $meeting = $this->build->meeting();
        $foundation = $this->build->foundOrgan($meeting, 'RPL', 'Replaycommissie');
        $member = $this->build->member();

        $this->build->install(
            $meeting,
            $foundation,
            $member,
            InstallationFunctions::Member,
        );
        $this->build->grantKey($this->build->meeting(), $member);
    }

    private function replay(): void
    {
        $kernel = self::$kernel;
        self::assertNotNull($kernel);

        $tester = new CommandTester(new Application($kernel)->find('report:generate:full'));

        self::assertSame(0, $tester->execute([], ['verbosity' => OutputInterface::VERBOSITY_QUIET]));

        // The rebuild wrote through a connection this manager does not know about row by row.
        $this->report->clear();
    }

    /**
     * @return array<string, int>
     */
    private function projectedRowCounts(): array
    {
        $connection = $this->report->getConnection();
        $counts = [];

        foreach ($this->projectedTables($connection) as $table) {
            $counts[$table] = (int) $connection->fetchOne(
                sprintf('SELECT count(*) FROM %s', $connection->quoteSingleIdentifier($table)),
            );
        }

        return $counts;
    }

    private function emptyTheProjection(): void
    {
        $connection = $this->report->getConnection();
        $tables = [];

        foreach ($this->projectedTables($connection) as $table) {
            $tables[] = $connection->quoteSingleIdentifier($table);
        }

        // Transactional in PostgreSQL, so this is rolled back with the rest of the test.
        $connection->executeStatement(
            sprintf('TRUNCATE TABLE %s RESTART IDENTITY CASCADE', implode(', ', $tables)),
        );
        $this->report->clear();
    }

    /**
     * @return string[]
     */
    private function projectedTables(Connection $connection): array
    {
        return $connection->fetchFirstColumn(
            <<<'SQL'
            SELECT tablename
            FROM pg_tables
            WHERE schemaname = 'public'
              AND tablename NOT LIKE 'doctrine_%'
            ORDER BY tablename
            SQL,
        );
    }
}
