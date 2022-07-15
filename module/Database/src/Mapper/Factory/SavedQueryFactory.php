<?php

namespace Database\Mapper\Factory;

use Database\Mapper\SavedQuery as SavedQueryMapper;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class SavedQueryFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return SavedQueryMapper
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): SavedQueryMapper {
        return new SavedQueryMapper($container->get('database_doctrine_em'));
    }
}
