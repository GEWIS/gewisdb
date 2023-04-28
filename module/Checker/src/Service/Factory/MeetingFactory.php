<?php

declare(strict_types=1);

namespace Checker\Service\Factory;

use Checker\Service\Meeting as MeetingService;
use Database\Service\Meeting as DatabaseMeetingService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class MeetingFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MeetingService {
        /** @var DatabaseMeetingService $meetingService */
        $meetingService = $container->get(DatabaseMeetingService::class);

        return new MeetingService($meetingService);
    }
}
