<?php

namespace Report;

use Report\Command\{
    Factory\GenerateFullCommandFactory,
    Factory\GeneratePartialCommandFactory,
    GenerateFullCommand,
    GeneratePartialCommand,
};
use Report\Listener\{
    DatabaseDeletionListener,
    DatabaseUpdateListener,
    Factory\DatabaseDeletionListenerFactory,
    Factory\DatabaseUpdateListenerFactory,
};
use Report\Service\{
    Board as BoardService,
    Factory\BoardFactory as BoardServiceFactory,
    Factory\MeetingFactory as MeetingServiceFactory,
    Factory\MemberFactory as MemberServiceFactory,
    Factory\MiscFactory as MiscServiceFactory,
    Factory\OrganFactory as OrganServiceFactory,
    Meeting as MeetingService,
    Member as MemberService,
    Misc as MiscService,
    Organ as OrganService,
};

class Module
{
    /**
     * Get the configuration for this module.
     *
     * @return array Module configuration
     */
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * Get service configuration.
     *
     * @return array Service configuration
     */
    public function getServiceConfig(): array
    {
        return [
            'factories' => [
                GenerateFullCommand::class => GenerateFullCommandFactory::class,
                GeneratePartialCommand::class => GeneratePartialCommandFactory::class,
                BoardService::class => BoardServiceFactory::class,
                MeetingService::class => MeetingServiceFactory::class,
                MemberService::class => MemberServiceFactory::class,
                MiscService::class => MiscServiceFactory::class,
                OrganService::class => OrganServiceFactory::class,
                DatabaseDeletionListener::class => DatabaseDeletionListenerFactory::class,
                DatabaseUpdateListener::class => DatabaseUpdateListenerFactory::class,
            ],
        ];
    }
}
