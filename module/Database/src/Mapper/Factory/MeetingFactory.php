<?php

declare(strict_types=1);

namespace Database\Mapper\Factory;

use Database\Mapper\Meeting as MeetingMapper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class MeetingFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MeetingMapper {
        return new MeetingMapper($container->get('database_doctrine_em'));
    }
}
