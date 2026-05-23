<?php

declare(strict_types=1);

namespace Report\Command\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;
use Report\Command\GeneratePartialCommand;
use Report\Service\Board as BoardService;
use Report\Service\Misc as MiscService;

class GeneratePartialCommandFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
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
