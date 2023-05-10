<?php

declare(strict_types=1);

namespace Checker\Service\Factory;

use Checker\Mapper\Member as MemberMapper;
use Checker\Service\Renewal as RenewalService;
use Database\Mapper\ActionLink as ActionLinkMapper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class RenewalFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): RenewalService {
        $config = $container->get('config');

        return new RenewalService(
            $container->get(ActionLinkMapper::class),
            $container->get(MemberMapper::class),
            $config,
        );
    }
}
