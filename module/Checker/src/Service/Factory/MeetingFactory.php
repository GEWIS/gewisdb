<?php

namespace Checker\Service\Factory;

use Checker\Service\Meeting as MeetingService;
use Database\Service\Meeting as DatabaseMeetingService;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class MeetingFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return MeetingService
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): MeetingService {
        /** @var DatabaseMeetingService $meetingService */
        $meetingService = $container->get(DatabaseMeetingService::class);

        return new MeetingService($meetingService);
    }
}
