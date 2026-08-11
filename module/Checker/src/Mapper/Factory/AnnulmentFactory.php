<?php

declare(strict_types=1);

namespace Checker\Mapper\Factory;

use Checker\Mapper\Annulment as AnnulmentMapper;
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
    ): AnnulmentMapper {
        return new AnnulmentMapper(
            $container->get('doctrine.entitymanager.orm_default'),
        );
    }
}
