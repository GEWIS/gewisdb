<?php

declare(strict_types=1);

namespace Report;

use Report\Command\Factory\GenerateFullCommandFactory;
use Report\Command\Factory\GeneratePartialCommandFactory;
use Report\Command\GenerateFullCommand;
use Report\Command\GeneratePartialCommand;
use Report\Listener\DatabaseDeletionListener;
use Report\Listener\DatabaseUpdateListener;
use Report\Listener\Factory\DatabaseDeletionListenerFactory;
use Report\Listener\Factory\DatabaseUpdateListenerFactory;
use Report\Mapper\Factory\MemberFactory as MemberMapperFactory;
use Report\Mapper\Member as MemberMapper;
use Report\Service\Board as BoardService;
use Report\Service\Factory\BoardFactory as BoardServiceFactory;
use Report\Service\Factory\KeyholderFactory as KeyholderServiceFactory;
use Report\Service\Factory\MeetingFactory as MeetingServiceFactory;
use Report\Service\Factory\MemberFactory as MemberServiceFactory;
use Report\Service\Factory\MiscFactory as MiscServiceFactory;
use Report\Service\Factory\OrganFactory as OrganServiceFactory;
use Report\Service\Factory\SubDecisionFactory as SubDecisionServiceFactory;
use Report\Service\Keyholder as KeyholderService;
use Report\Service\Meeting as MeetingService;
use Report\Service\Member as MemberService;
use Report\Service\Misc as MiscService;
use Report\Service\Organ as OrganService;
use Report\Service\SubDecision as SubDecisionService;

class Module
{
    /**
     * Get the configuration for this module.
     */
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * Get service configuration.
     */
    public function getServiceConfig(): array
    {
        return [
            'factories' => [
                GenerateFullCommand::class => GenerateFullCommandFactory::class,
                GeneratePartialCommand::class => GeneratePartialCommandFactory::class,
                BoardService::class => BoardServiceFactory::class,
                KeyholderService::class => KeyholderServiceFactory::class,
                MeetingService::class => MeetingServiceFactory::class,
                MemberService::class => MemberServiceFactory::class,
                MemberMapper::class => MemberMapperFactory::class,
                MiscService::class => MiscServiceFactory::class,
                OrganService::class => OrganServiceFactory::class,
                SubDecisionService::class => SubDecisionServiceFactory::class,
                DatabaseDeletionListener::class => DatabaseDeletionListenerFactory::class,
                DatabaseUpdateListener::class => DatabaseUpdateListenerFactory::class,
            ],
        ];
    }
}
