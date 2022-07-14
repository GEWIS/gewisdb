<?php

namespace Database\Service\Factory;

use Checker\Service\Checker as CheckerService;
use Database\Mapper\Event as EventMapper;
use Database\Service\Event as EventService;
use Database\Service\InstallationFunction as InstallationFunctionService;
use Database\Service\MailingList as MailingListService;
use Database\Service\Meeting as MeetingService;
use Database\Service\Member as MemberService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class EventFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return EventService
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): EventService {
        /** @var EventMapper $eventMapper */
        $eventMapper = $container->get(EventMapper::class);
        $services = [
            InstallationFunctionService::class,
            MailingListService::class,
            MeetingService::class,
            MemberService::class,
            CheckerService::class,
        ];

        return new EventService(
            $eventMapper,
            $services
        );
    }
}
