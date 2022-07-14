<?php

namespace Report\Command\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Report\Command\GenerateFullCommand;
use Report\Service\{
    Board as BoardService,
    Meeting as MeetingService,
    Member as MemberService,
    Misc as MiscService,
    Organ as OrganService,
};
use Report\Command\GeneratePartialCommand;

class GeneratePartialCommandFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return GeneratePartialCommand
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): GeneratePartialCommand {
        /** @var BoardService $boardService */
        $boardService = $container->get(BoardService::class);
        /** @var MiscService $miscService */
        $miscService = $container->get(MiscService::class);

        return new GeneratePartialCommand(
            $boardService,
            $miscService,
        );
    }
}
