<?php

namespace Checker\Mapper\Factory;

use Checker\Mapper\Key as KeyMapper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class KeyFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return KeyMapper
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): KeyMapper {
        return new KeyMapper(
            $container->get('database_doctrine_em'),
        );
    }
}
