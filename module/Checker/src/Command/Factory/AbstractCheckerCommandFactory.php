<?php

namespace Checker\Command\Factory;

use Checker\Service\Checker as CheckerService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AbstractCheckerCommandFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ) {
        /** @var CheckerService $checkerService */
        $checkerService = $container->get(CheckerService::class);

        return new $requestedName($checkerService);
    }
}
