<?php

namespace Database\Controller\Factory;

use Database\Controller\ProspectiveMemberController;
use Database\Service\Member as MemberService;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class ProspectiveMemberControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return ProspectiveMemberController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): ProspectiveMemberController {
        /** @var MemberService $memberService */
        $memberService = $container->get(MemberService::class);

        return new ProspectiveMemberController($memberService);
    }
}
