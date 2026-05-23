<?php

declare(strict_types=1);

namespace Database\Command\Factory;

use Database\Command\DeleteExpiredMembersCommand;
use Database\Service\Member as MemberService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class DeleteExpiredMembersCommandFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): DeleteExpiredMembersCommand {
        /** @var MemberService $memberService */
        $memberService = $container->get(MemberService::class);

        return new DeleteExpiredMembersCommand($memberService);
    }
}
