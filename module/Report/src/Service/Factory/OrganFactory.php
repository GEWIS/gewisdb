<?php

namespace Report\Service\Factory;

use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;
use Report\Service\Organ as OrganService;
use Zend\ServiceManager\Factory\FactoryInterface;

class OrganFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return OrganService
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): OrganService {
        /** @var EntityManager $emReport */
        $emReport = $container->get('doctrine.entitymanager.orm_report');

        return new OrganService($emReport);
    }
}
