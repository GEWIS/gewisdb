<?php

declare(strict_types=1);

namespace Application\Mapper\Factory;

use Application\Mapper\ConfigItem as ConfigItemMapper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ConfigItemFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ConfigItemMapper {
        return new ConfigItemMapper($container->get('database_doctrine_em'));
    }
}
