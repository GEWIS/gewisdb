<?php

namespace Report\Command\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Report\Service\{
    Board as BoardService,
    Misc as MiscService,
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
        array $options = null,
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
