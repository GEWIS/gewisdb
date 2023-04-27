<?php

declare(strict_types=1);

namespace Database\Controller\Factory;

use Database\Controller\ExportController;
use Database\Service\Meeting as MeetingService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ExportControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ExportController {
        /** @var MeetingService $meetingService */
        $meetingService = $container->get(MeetingService::class);

        return new ExportController($meetingService);
    }
}
