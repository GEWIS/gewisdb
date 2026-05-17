<?php

declare(strict_types=1);

namespace Report\Service\Factory;

use Doctrine\ORM\EntityManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;
use Report\Service\Board as BoardService;

class BoardFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): BoardService {
        /** @var EntityManager $emReport */
        $emReport = $container->get('doctrine.entitymanager.orm_report');

        return new BoardService($emReport);
    }
}
