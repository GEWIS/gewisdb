<?php

declare(strict_types=1);

namespace Database\Mapper\Factory;

use Database\Mapper\ActionLink as ActionLinkMapper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ActionLinkFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ActionLinkMapper {
        return new ActionLinkMapper($container->get('doctrine.entitymanager.orm_default'));
    }
}
