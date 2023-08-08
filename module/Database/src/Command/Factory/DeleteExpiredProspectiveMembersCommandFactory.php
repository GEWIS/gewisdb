<?php

declare(strict_types=1);

namespace Database\Command\Factory;

use Database\Command\DeleteExpiredProspectiveMembersCommand;
use Database\Service\Member as MemberService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class DeleteExpiredProspectiveMembersCommandFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): DeleteExpiredProspectiveMembersCommand {
        /** @var MemberService $memberService */
        $memberService = $container->get(MemberService::class);

        return new DeleteExpiredProspectiveMembersCommand($memberService);
    }
}
