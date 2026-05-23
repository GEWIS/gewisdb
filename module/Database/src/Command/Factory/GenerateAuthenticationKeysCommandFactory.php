<?php

declare(strict_types=1);

namespace Database\Command\Factory;

use Database\Command\GenerateAuthenticationKeysCommand;
use Database\Service\Member as MemberService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class GenerateAuthenticationKeysCommandFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): GenerateAuthenticationKeysCommand {
        /** @var MemberService $memberService */
        $memberService = $container->get(MemberService::class);

        return new GenerateAuthenticationKeysCommand($memberService);
    }
}
