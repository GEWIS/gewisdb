<?php

declare(strict_types=1);

namespace Report\Command\Factory;

use Database\Service\Api as ApiService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;
use Report\Command\GenerateFullCommand;
use Report\Service\Meeting as MeetingService;
use Report\Service\Member as MemberService;
use Report\Service\Misc as MiscService;

class GenerateFullCommandFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): GenerateFullCommand {
        /** @var ApiService $apiService */
        $apiService = $container->get(ApiService::class);
        /** @var MeetingService $meetingService */
        $meetingService = $container->get(MeetingService::class);
        /** @var MemberService $memberService */
        $memberService = $container->get(MemberService::class);
        /** @var MiscService $miscService */
        $miscService = $container->get(MiscService::class);

        return new GenerateFullCommand(
            $apiService,
            $meetingService,
            $memberService,
            $miscService,
        );
    }
}
