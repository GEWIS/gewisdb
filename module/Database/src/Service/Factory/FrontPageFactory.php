<?php

declare(strict_types=1);

namespace Database\Service\Factory;

use Database\Service\Api as ApiService;
use Database\Service\FrontPage as FrontPageService;
use Database\Service\Mailman as MailmanService;
use Database\Service\Member as MemberService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class FrontPageFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): FrontPageService {
        $apiService = $container->get(ApiService::class);
        $mailmanService = $container->get(MailmanService::class);
        $memberService = $container->get(MemberService::class);

        return new FrontPageService(
            $apiService,
            $mailmanService,
            $memberService,
        );
    }
}
