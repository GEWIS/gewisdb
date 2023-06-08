<?php

declare(strict_types=1);

namespace Database\Controller\Factory;

use Database\Controller\ProspectiveMemberController;
use Database\Service\Member as MemberService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ProspectiveMemberControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ProspectiveMemberController {
        /** @var MemberService $memberService */
        $memberService = $container->get(MemberService::class);

        return new ProspectiveMemberController($memberService);
    }
}