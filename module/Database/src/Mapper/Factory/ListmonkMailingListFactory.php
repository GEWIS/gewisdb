<?php

declare(strict_types=1);

namespace Database\Mapper\Factory;

use Database\Mapper\ListmonkMailingList as ListmonkMailingListMapper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ListmonkMailingListFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ListmonkMailingListMapper {
        return new ListmonkMailingListMapper($container->get('doctrine.entitymanager.orm_default'));
    }
}