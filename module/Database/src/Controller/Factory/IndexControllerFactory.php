<?php

namespace Database\Controller\Factory;

use Database\Controller\IndexController;
use Database\Service\Member as MemberService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class IndexControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return IndexController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): IndexController {
        /** @var MemberService $memberService */
        $memberService = $container->get(MemberService::class);

        return new IndexController($memberService);
    }
}
