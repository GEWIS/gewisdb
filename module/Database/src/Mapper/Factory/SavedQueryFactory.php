<?php

declare(strict_types=1);

namespace Database\Mapper\Factory;

use Database\Mapper\SavedQuery as SavedQueryMapper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class SavedQueryFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): SavedQueryMapper {
        return new SavedQueryMapper($container->get('database_doctrine_em'));
    }
}
