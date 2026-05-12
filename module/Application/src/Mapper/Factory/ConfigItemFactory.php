<?php

declare(strict_types=1);

namespace Application\Mapper\Factory;

use Application\Mapper\ConfigItem as ConfigItemMapper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class ConfigItemFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ConfigItemMapper {
        return new ConfigItemMapper($container->get('doctrine.entitymanager.orm_default'));
    }
}
