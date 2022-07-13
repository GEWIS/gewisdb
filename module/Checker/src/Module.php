<?php

namespace Checker;

use Checker\Mapper\Factory\InstallationFactory as InstallationMapperFactory;
use Checker\Mapper\Factory\MemberFactory as MemberMapperFactory;
use Checker\Mapper\Factory\OrganFactory as OrganMapperFactory;
use Checker\Mapper\Installation as InstallationMapper;
use Checker\Mapper\Member as MemberMapper;
use Checker\Mapper\Organ as OrganMapper;
use Checker\Service\Checker as CheckerService;
use Checker\Service\Factory\CheckerFactory as CheckerServiceFactory;
use Checker\Service\Factory\InstallationFactory as InstallationServiceFactory;
use Checker\Service\Factory\MeetingFactory as MeetingServiceFactory;
use Checker\Service\Factory\MemberFactory as MemberServiceFactory;
use Checker\Service\Factory\OrganFactory as OrganServiceFactory;
use Checker\Service\Installation as InstallationService;
use Checker\Service\Meeting as MeetingService;
use Checker\Service\Member as MemberService;
use Checker\Service\Organ as OrganService;
use Interop\Container\ContainerInterface;

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
                CheckerService::class => CheckerServiceFactory::class,
                InstallationService::class => InstallationServiceFactory::class,
                MeetingService::class => MeetingServiceFactory::class,
                MemberService::class => MemberServiceFactory::class,
                OrganService::class => OrganServiceFactory::class,
                InstallationMapper::class => InstallationMapperFactory::class,
                MemberMapper::class => MemberMapperFactory::class,
                OrganMapper::class => OrganMapperFactory::class,
                'checker_mail_transport' => function (ContainerInterface $container) {
                    $config = $container->get('config');
                    $config = $config['email'];
                    $class = '\Zend\Mail\Transport\\' . $config['transport'];
                    $optionsClass = '\Zend\Mail\Transport\\' . $config['transport'] . 'Options';
                    $transport = new $class();
                    $transport->setOptions(new $optionsClass($config['options']));
                    return $transport;
                }
            )
        );
    }
}
