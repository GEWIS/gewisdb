<?php

declare(strict_types=1);

namespace Checker;

use Checker\Command\CheckAuthenticationKeysCommand;
use Checker\Command\CheckDatabaseCommand;
use Checker\Command\CheckDischargesCommand;
use Checker\Command\CheckMembershipExpirationCommand;
use Checker\Command\CheckMembershipGraduateRenewalCommand;
use Checker\Command\CheckMembershipTUeCommand;
use Checker\Command\CheckMembershipTypeCommand;
use Checker\Command\Factory\AbstractCheckerCommandFactory;
use Checker\Command\Factory\CheckMembershipGraduateRenewalCommandFactory;
use Checker\Command\Factory\SendBirthdayMailCommandFactory;
use Checker\Command\SendBirthdayMailCommand;
use Checker\Mapper\Factory\InstallationFactory as InstallationMapperFactory;
use Checker\Mapper\Factory\KeyFactory as KeyMapperFactory;
use Checker\Mapper\Factory\MemberFactory as MemberMapperFactory;
use Checker\Mapper\Factory\OrganFactory as OrganMapperFactory;
use Checker\Mapper\Installation as InstallationMapper;
use Checker\Mapper\Key as KeyMapper;
use Checker\Mapper\Member as MemberMapper;
use Checker\Mapper\Organ as OrganMapper;
use Checker\Service\Birthday;
use Checker\Service\Checker as CheckerService;
use Checker\Service\Factory\BirthdayFactory;
use Checker\Service\Factory\CheckerFactory as CheckerServiceFactory;
use Checker\Service\Factory\InstallationFactory as InstallationServiceFactory;
use Checker\Service\Factory\KeyFactory as KeyServiceFactory;
use Checker\Service\Factory\MeetingFactory as MeetingServiceFactory;
use Checker\Service\Factory\MemberFactory as MemberServiceFactory;
use Checker\Service\Factory\OrganFactory as OrganServiceFactory;
use Checker\Service\Factory\RenewalFactory as RenewalServiceFactory;
use Checker\Service\Installation as InstallationService;
use Checker\Service\Key as KeyService;
use Checker\Service\Meeting as MeetingService;
use Checker\Service\Member as MemberService;
use Checker\Service\Organ as OrganService;
use Checker\Service\Renewal as RenewalService;
use Psr\Container\ContainerInterface;

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
                CheckAuthenticationKeysCommand::class => AbstractCheckerCommandFactory::class,
                CheckDatabaseCommand::class => AbstractCheckerCommandFactory::class,
                CheckDischargesCommand::class => AbstractCheckerCommandFactory::class,
                CheckMembershipExpirationCommand::class => AbstractCheckerCommandFactory::class,
                CheckMembershipGraduateRenewalCommand::class => CheckMembershipGraduateRenewalCommandFactory::class,
                CheckMembershipTUeCommand::class => AbstractCheckerCommandFactory::class,
                CheckMembershipTypeCommand::class => AbstractCheckerCommandFactory::class,
                SendBirthdayMailCommand::class => SendBirthdayMailCommandFactory::class,
                CheckerService::class => CheckerServiceFactory::class,
                InstallationService::class => InstallationServiceFactory::class,
                KeyService::class => KeyServiceFactory::class,
                MeetingService::class => MeetingServiceFactory::class,
                MemberService::class => MemberServiceFactory::class,
                OrganService::class => OrganServiceFactory::class,
                RenewalService::class => RenewalServiceFactory::class,
                InstallationMapper::class => InstallationMapperFactory::class,
                KeyMapper::class => KeyMapperFactory::class,
                MemberMapper::class => MemberMapperFactory::class,
                OrganMapper::class => OrganMapperFactory::class,
                Birthday::class => BirthdayFactory::class,
                'checker_mail_transport' => static function (ContainerInterface $container) {
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
