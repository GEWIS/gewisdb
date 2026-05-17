<?php

declare(strict_types=1);

namespace Report\Mapper\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;
use Report\Mapper\Member as MemberMapper;

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
        return new MemberMapper($container->get('doctrine.entitymanager.orm_report'));
    }
}
