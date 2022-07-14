<?php

namespace Database\Mapper\Factory;

use Database\Mapper\Meeting as MeetingMapper;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class MeetingFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return MeetingMapper
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): MeetingMapper {
        return new MeetingMapper($container->get('database_doctrine_em'));
    }
}
