<?php

declare(strict_types=1);

namespace Database\Mapper\Factory;

use Database\Mapper\ProspectiveMember as ProspectiveMemberMapper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class ProspectiveMemberFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ProspectiveMemberMapper {
        return new ProspectiveMemberMapper($container->get('doctrine.entitymanager.orm_default'));
    }
}
