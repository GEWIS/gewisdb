<?php

namespace Checker;

use Checker\Command\{
    CheckAuthenticationKeysCommand,
    CheckDatabaseCommand,
    CheckDischargesCommand,
    CheckMembershipTUeCommand,
    CheckMembershipTypeCommand,
    CheckMembershipExpirationCommand,
};
use Checker\Command\Factory\AbstractCheckerCommandFactory;
use Checker\Mapper\{
    Installation as InstallationMapper,
    Key as KeyMapper,
    Member as MemberMapper,
    Organ as OrganMapper};
use Checker\Mapper\Factory\{
    InstallationFactory as InstallationMapperFactory,
    KeyFactory as KeyMapperFactory,
    MemberFactory as MemberMapperFactory,
    OrganFactory as OrganMapperFactory};
use Checker\Service\{
    Checker as CheckerService,
    Installation as InstallationService,
    Key as KeyService,
    Meeting as MeetingService,
    Member as MemberService,
    Organ as OrganService};
use Checker\Service\Factory\{
    CheckerFactory as CheckerServiceFactory,
    InstallationFactory as InstallationServiceFactory,
    KeyFactory as KeyServiceFactory,
    MeetingFactory as MeetingServiceFactory,
    MemberFactory as MemberServiceFactory,
    OrganFactory as OrganServiceFactory};
use Psr\Container\ContainerInterface;

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
                CheckAuthenticationKeysCommand::class => AbstractCheckerCommandFactory::class,
                CheckDatabaseCommand::class => AbstractCheckerCommandFactory::class,
                CheckDischargesCommand::class => AbstractCheckerCommandFactory::class,
                CheckMembershipExpirationCommand::class => AbstractCheckerCommandFactory::class,
                CheckMembershipTUeCommand::class => AbstractCheckerCommandFactory::class,
                CheckMembershipTypeCommand::class => AbstractCheckerCommandFactory::class,
                CheckerService::class => CheckerServiceFactory::class,
                InstallationService::class => InstallationServiceFactory::class,
                KeyService::class => KeyServiceFactory::class,
                MeetingService::class => MeetingServiceFactory::class,
                MemberService::class => MemberServiceFactory::class,
                OrganService::class => OrganServiceFactory::class,
                InstallationMapper::class => InstallationMapperFactory::class,
                KeyMapper::class => KeyMapperFactory::class,
                MemberMapper::class => MemberMapperFactory::class,
                OrganMapper::class => OrganMapperFactory::class,
                'checker_mail_transport' => function (ContainerInterface $container) {
                    $config = $container->get('config');
                    $config = $config['email'];
                    $class = '\Laminas\Mail\Transport\\' . $config['transport'];
                    $optionsClass = '\Laminas\Mail\Transport\\' . $config['transport'] . 'Options';
                    $transport = new $class();
                    $transport->setOptions(new $optionsClass($config['options']));
                    return $transport;
                },
            ],
        ];
    }
}
