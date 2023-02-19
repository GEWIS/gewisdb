<?php

namespace Report\Command\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Report\Command\GenerateFullCommand;
use Report\Service\{
    Board as BoardService,
    Keyholder as KeyholderService,
    Meeting as MeetingService,
    Member as MemberService,
    Misc as MiscService,
    Organ as OrganService,
};

class GenerateFullCommandFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return GenerateFullCommand
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): GenerateFullCommand {
        /** @var BoardService $boardService */
        $boardService = $container->get(BoardService::class);
        /** @var KeyholderService $keyholderService */
        $keyholderService = $container->get(KeyholderService::class);
        /** @var MeetingService $meetingService */
        $meetingService = $container->get(MeetingService::class);
        /** @var MemberService $memberService */
        $memberService = $container->get(MemberService::class);
        /** @var MiscService $miscService */
        $miscService = $container->get(MiscService::class);
        /** @var OrganService $organService */
        $organService = $container->get(OrganService::class);

        return new GenerateFullCommand(
            $boardService,
            $keyholderService,
            $meetingService,
            $memberService,
            $miscService,
            $organService,
        );
    }
}
