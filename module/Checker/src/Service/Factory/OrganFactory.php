<?php

namespace Checker\Service\Factory;

use Checker\Mapper\Organ as OrganMapper;
use Checker\Service\Organ as OrganService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

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
        array $options = null,
    ): OrganService {
        /** @var OrganMapper $organMapper */
        $organMapper = $container->get(OrganMapper::class);

        return new OrganService($organMapper);
    }
}
