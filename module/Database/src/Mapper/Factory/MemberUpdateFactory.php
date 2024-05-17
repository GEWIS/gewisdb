<?php

declare(strict_types=1);

namespace Database\Mapper\Factory;

use Database\Mapper\MemberUpdate as MemberUpdateMapper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class MemberUpdateFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MemberUpdateMapper {
        return new MemberUpdateMapper($container->get('doctrine.entitymanager.orm_default'));
    }
}
