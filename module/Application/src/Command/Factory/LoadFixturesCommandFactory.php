<?php

declare(strict_types=1);

namespace Application\Command\Factory;

use Application\Command\LoadFixturesCommand;
use Doctrine\ORM\EntityManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class LoadFixturesCommandFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): LoadFixturesCommand {
        /** @var EntityManager $databaseEntityManager */
        $databaseEntityManager = $container->get('doctrine.entitymanager.orm_default');
        /** @var EntityManager $reportEntityManager */
        $reportEntityManager = $container->get('doctrine.entitymanager.orm_report');

        return new LoadFixturesCommand(
            $databaseEntityManager,
            $reportEntityManager,
        );
    }
}
