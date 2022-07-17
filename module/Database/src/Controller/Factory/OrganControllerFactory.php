<?php

namespace Database\Controller\Factory;

use Database\Controller\OrganController;
use Database\Service\Meeting as MeetingService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class OrganControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return OrganController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): OrganController {
        /** @var MeetingService $meetingService */
        $meetingService = $container->get(MeetingService::class);

        return new OrganController($meetingService);
    }
}
