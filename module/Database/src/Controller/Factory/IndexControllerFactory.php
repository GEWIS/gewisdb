<?php

declare(strict_types=1);

namespace Database\Controller\Factory;

use Database\Controller\IndexController;
use Database\Service\FrontPage as FrontPageService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class IndexControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): IndexController {
        $frontPageService = $container->get(FrontPageService::class);

        return new IndexController($frontPageService);
    }
}
