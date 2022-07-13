<?php

namespace Database\Mapper\Factory;

use Database\Mapper\Event as EventMapper;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class EventFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return EventMapper
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): EventMapper {
        return new EventMapper($container->get('database_doctrine_em'));
    }
}
