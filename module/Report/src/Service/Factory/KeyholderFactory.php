<?php

declare(strict_types=1);

namespace Report\Service\Factory;

use Doctrine\ORM\EntityManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;
use Report\Service\Keyholder as KeyholderService;

class KeyholderFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): KeyholderService {
        /** @var EntityManager $emReport */
        $emReport = $container->get('doctrine.entitymanager.orm_report');

        return new KeyholderService($emReport);
    }
}
