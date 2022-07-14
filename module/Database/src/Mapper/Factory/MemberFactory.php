<?php

namespace Database\Mapper\Factory;

use Database\Mapper\Member as MemberMapper;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

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
        array $options = null
    ): MemberMapper {
        return new MemberMapper($container->get('database_doctrine_em'));
    }
}