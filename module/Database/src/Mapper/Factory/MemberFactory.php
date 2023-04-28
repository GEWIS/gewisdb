<?php

declare(strict_types=1);

namespace Database\Mapper\Factory;

use Database\Mapper\Member as MemberMapper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class MemberFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MemberMapper {
        return new MemberMapper($container->get('database_doctrine_em'));
    }
}
