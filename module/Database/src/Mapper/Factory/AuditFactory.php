<?php

declare(strict_types=1);

namespace Database\Mapper\Factory;

use Database\Mapper\Audit as AuditMapper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class AuditFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AuditMapper {
        return new AuditMapper($container->get('database_doctrine_em'));
    }
}
