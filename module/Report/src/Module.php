<?php

namespace Report;

use DoctrineModule\Service\DriverFactory;
use DoctrineModule\Service\EventManagerFactory;
use DoctrineORMModule\Form\Annotation\AnnotationBuilder;
use DoctrineORMModule\Service\ConfigurationFactory;
use DoctrineORMModule\Service\DBALConnectionFactory;
use DoctrineORMModule\Service\EntityManagerFactory;
use DoctrineORMModule\Service\EntityResolverFactory;
use DoctrineORMModule\Service\SQLLoggerCollectorFactory;
use Interop\Container\ContainerInterface;
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
                BoardService::class => new BoardServiceFactory(),
                MeetingService::class => new MeetingServiceFactory(),
                MemberService::class => new MemberServiceFactory(),
                MiscService::class => new MiscServiceFactory(),
                OrganService::class => new OrganServiceFactory(),
                DatabaseDeletionListener::class => new DatabaseDeletionListenerFactory(),
                DatabaseUpdateListener::class => new DatabaseUpdateListenerFactory(),
                'doctrine.connection.orm_report' => new DBALConnectionFactory('orm_report'),
                'doctrine.configuration.orm_report' => new ConfigurationFactory('orm_report'),
                'doctrine.entitymanager.orm_report' => new EntityManagerFactory('orm_report'),
                'doctrine.driver.orm_report' => new DriverFactory('orm_report'),
                'doctrine.eventmanager.orm_report' => new EventManagerFactory('orm_report'),
                'doctrine.entity_resolver.orm_report' => new EntityResolverFactory('orm_report'),
                'doctrine.sql_logger_collector.orm_report' => new SQLLoggerCollectorFactory('orm_report'),
                'DoctrineORMModule\Form\Annotation\AnnotationBuilder' => function (ContainerInterface $container) {
                    return new AnnotationBuilder($container->get('doctrine.entitymanager.orm_report'));
                },
            )
        );
    }
}
