<?php

declare(strict_types=1);

namespace Checker\Mapper\Factory;

use Checker\Mapper\Key as KeyMapper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class KeyFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): KeyMapper {
        return new KeyMapper(
            $container->get('doctrine.entitymanager.orm_default'),
        );
    }
}
