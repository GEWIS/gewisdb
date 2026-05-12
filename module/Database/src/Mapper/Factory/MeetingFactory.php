<?php

declare(strict_types=1);

namespace Database\Mapper\Factory;

use Database\Mapper\Meeting as MeetingMapper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class MeetingFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MeetingMapper {
        return new MeetingMapper($container->get('doctrine.entitymanager.orm_default'));
    }
}
