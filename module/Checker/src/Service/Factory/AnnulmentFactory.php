<?php

declare(strict_types=1);

namespace Checker\Service\Factory;

use Checker\Mapper\Annulment as AnnulmentMapper;
use Checker\Service\Annulment as AnnulmentService;
use Database\Service\Annulment as DatabaseAnnulmentService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class AnnulmentFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AnnulmentService {
        return new AnnulmentService(
            $container->get(AnnulmentMapper::class),
            $container->get(DatabaseAnnulmentService::class),
        );
    }
}
