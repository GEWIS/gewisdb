<?php

declare(strict_types=1);

namespace Checker\Command\Factory;

use Checker\Command\CheckMembershipGraduateRenewalCommand;
use Checker\Service\Renewal as RenewalService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class CheckMembershipGraduateRenewalCommandFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): CheckMembershipGraduateRenewalCommand {
        return new CheckMembershipGraduateRenewalCommand(
            $container->get(RenewalService::class),
        );
    }
}
