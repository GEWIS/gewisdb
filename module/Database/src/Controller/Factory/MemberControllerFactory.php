<?php

namespace Database\Controller\Factory;

use Checker\Service\Checker as CheckerService;
use Database\Controller\MemberController;
use Database\Service\Member as MemberService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class MemberControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return MemberController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): MemberController {
        /** @var MemberService $memberService */
        $memberService = $container->get(MemberService::class);
        /** @var CheckerService $checkerService */
        $checkerService = $container->get(CheckerService::class);

        return new MemberController(
            $memberService,
            $checkerService,
        );
    }
}
