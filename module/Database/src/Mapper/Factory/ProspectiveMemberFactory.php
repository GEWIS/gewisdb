<?php

namespace Database\Mapper\Factory;

use Database\Mapper\ProspectiveMember as ProspectiveMemberMapper;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class ProspectiveMemberFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return ProspectiveMemberMapper
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): ProspectiveMemberMapper {
        return new ProspectiveMemberMapper($container->get('database_doctrine_em'));
    }
}
