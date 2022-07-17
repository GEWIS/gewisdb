<?php

namespace Checker\Mapper\Factory;

use Checker\Mapper\Member as MemberMapper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class MemberFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return MemberMapper
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): MemberMapper {
        return new MemberMapper(
            $container->get('database_doctrine_em'),
        );
    }
}
