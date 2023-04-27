<?php

declare(strict_types=1);

namespace Database\Controller\Factory;

use Database\Controller\OrganController;
use Database\Service\Meeting as MeetingService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class OrganControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): OrganController {
        /** @var MeetingService $meetingService */
        $meetingService = $container->get(MeetingService::class);

        return new OrganController($meetingService);
    }
}
