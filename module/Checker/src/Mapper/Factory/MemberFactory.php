<?php

declare(strict_types=1);

namespace Checker\Mapper\Factory;

use Checker\Mapper\Member as MemberMapper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class MemberFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MemberMapper {
        return new MemberMapper(
            $container->get('doctrine.entitymanager.orm_default'),
        );
    }
}
