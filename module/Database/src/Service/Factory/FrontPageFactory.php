<?php

declare(strict_types=1);

namespace Database\Service\Factory;

use Database\Service\Api as ApiService;
use Database\Service\FrontPage as FrontPageService;
use Database\Service\Mailman as MailmanService;
use Database\Service\Listmonk as ListmonkService;
use Database\Service\Member as MemberService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class FrontPageFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): FrontPageService {
        $apiService = $container->get(ApiService::class);
        $mailmanService = $container->get(MailmanService::class);
        $listmonkService = $container->get(ListmonkService::class);
        $memberService = $container->get(MemberService::class);

        return new FrontPageService(
            $apiService,
            $mailmanService,
            $listmonkService,
            $memberService,
        );
    }
}
