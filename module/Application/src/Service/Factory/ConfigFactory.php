<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Mapper\ConfigItem as ConfigItemMapper;
use Application\Service\Config as ConfigService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ConfigFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ConfigService {
        $configItemMapper = $container->get(ConfigItemMapper::class);

        return new ConfigService($configItemMapper);
    }
}
