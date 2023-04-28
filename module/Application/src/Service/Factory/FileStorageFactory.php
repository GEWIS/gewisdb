<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Service\FileStorage as FileStorageService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class FileStorageFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): FileStorageService {
        /** @var array $config */
        $config = $container->get('config');

        return new FileStorageService($config);
    }
}
