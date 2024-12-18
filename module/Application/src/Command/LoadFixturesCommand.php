<?php

declare(strict_types=1);

namespace Application\Command;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'application:fixtures:load',
    description: 'Seed the database with data fixtures.',
)]
class LoadFixturesCommand extends Command
{
    private const array DATABASE_FIXTURES = [
        './module/Database/test/Seeder',
        './module/User/test/Seeder',
    ];

    private const array REPORT_FIXTURES = [
        // './module/Report/test/Seeder',
    ];

    public function __construct(
        private readonly EntityManager $databaseEntityManager,
        private readonly EntityManager $reportEntityManager,
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $output->setDecorated(true);

        $databaseLoader = new Loader();
        $reportLoader = new Loader();
        $databasePurger = new ORMPurger();
        $databasePurger->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);
        $databaseExecutor = new ORMExecutor($this->databaseEntityManager, $databasePurger);
        $reportPurger = new ORMPurger();
        $reportPurger->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);
        $reportExecutor = new ORMExecutor($this->reportEntityManager, $reportPurger);

        foreach ($this::DATABASE_FIXTURES as $fixture) {
            $databaseLoader->loadFromDirectory($fixture);
        }

        foreach ($this::REPORT_FIXTURES as $fixture) {
            $reportLoader->loadFromDirectory($fixture);
        }

        $output->writeln('<info>Loading fixtures into the database...</info>');

        $databaseConnection = $this->databaseEntityManager->getConnection();
        $reportConnection = $this->reportEntityManager->getConnection();
        try {
            // Temporarily disable FK constraint checks.
            // The try-catch is necessary to hide some error messages (because the executeStatement).
            $databaseConnection->executeStatement('SET session_replication_role = \'replica\'');
            $databaseExecutor->execute($databaseLoader->getFixtures());
            $databaseConnection->executeStatement('SET session_replication_role = \'origin\'');
            $reportConnection->executeStatement('SET session_replication_role = \'replica\'');
            $reportExecutor->execute($reportLoader->getFixtures());
            $reportConnection->executeStatement('SET session_replication_role = \'origin\'');
        } catch (Throwable $e) {
            $output->writeln('<comment>' . $e->getMessage() . '</comment>');
        }

        $output->writeln('<info>Loaded fixtures!</info>');

        return Command::SUCCESS;
    }
}
