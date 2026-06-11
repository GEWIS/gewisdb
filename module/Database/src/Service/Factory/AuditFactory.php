<?php

declare(strict_types=1);

namespace Database\Service\Factory;

use Database\Mapper\Audit as AuditMapper;
use Database\Service\Audit as AuditService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class AuditFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AuditService {
        /** @var AuditMapper $auditMapper */
        $auditMapper = $container->get(AuditMapper::class);

        return new AuditService($auditMapper);
    }
}
