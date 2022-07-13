<?php

namespace Checker\Controller\Factory;

use Checker\Controller\CheckerController;
use Checker\Service\Checker as CheckerService;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class CheckerControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return CheckerController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): CheckerController {
        /** @var CheckerService $checkerService */
        $checkerService = $container->get(CheckerService::class);

        return new CheckerController($checkerService);
    }
}
