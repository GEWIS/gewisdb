<?php

namespace Application\Service\Factory;

use Application\Service\FileStorage as FileStorageService;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class FileStorageFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return FileStorageService
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): FileStorageService {
        /** @var array $config */
        $config = $container->get('config');

        return new FileStorageService($config);
    }
}