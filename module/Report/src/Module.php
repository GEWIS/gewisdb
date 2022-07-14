<?php

namespace Report;

use Report\Command\Factory\GenerateFullCommandFactory;
use Report\Command\Factory\GeneratePartialCommandFactory;
use Report\Command\GenerateFullCommand;
use Report\Command\GeneratePartialCommand;
use Report\Listener\DatabaseDeletionListener;
use Report\Listener\DatabaseUpdateListener;
use Report\Listener\Factory\DatabaseDeletionListenerFactory;
use Report\Listener\Factory\DatabaseUpdateListenerFactory;
use Report\Service\Board as BoardService;
use Report\Service\Factory\BoardFactory as BoardServiceFactory;
use Report\Service\Factory\MeetingFactory as MeetingServiceFactory;
use Report\Service\Factory\MemberFactory as MemberServiceFactory;
use Report\Service\Factory\MiscFactory as MiscServiceFactory;
use Report\Service\Factory\OrganFactory as OrganServiceFactory;
use Report\Service\Meeting as MeetingService;
use Report\Service\Member as MemberService;
use Report\Service\Misc as MiscService;
use Report\Service\Organ as OrganService;

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
        return array(
            'factories' => array(
                GenerateFullCommand::class => GenerateFullCommandFactory::class,
                GeneratePartialCommand::class => GeneratePartialCommandFactory::class,
                BoardService::class => BoardServiceFactory::class,
                MeetingService::class => MeetingServiceFactory::class,
                MemberService::class => MemberServiceFactory::class,
                MiscService::class => MiscServiceFactory::class,
                OrganService::class => OrganServiceFactory::class,
                DatabaseDeletionListener::class => DatabaseDeletionListenerFactory::class,
                DatabaseUpdateListener::class => DatabaseUpdateListenerFactory::class,
            )
        );
    }
}
