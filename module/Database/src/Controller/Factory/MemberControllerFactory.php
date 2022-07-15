<?php

namespace Database\Controller\Factory;

use Database\Controller\MemberController;
use Database\Service\Member as MemberService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

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

        return new MemberController($memberService);
    }
}
