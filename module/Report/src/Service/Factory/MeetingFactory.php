<?php

namespace Report\Service\Factory;

use Database\Mapper\Meeting as MeetingMapper;
use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;
use Report\Service\Meeting as MeetingService;
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
        /** @var MeetingMapper $meetingMapper */
        $meetingMapper = $container->get(MeetingMapper::class);
        /** @var EntityManager $emReport */
        $emReport = $container->get('doctrine.entitymanager.orm_report');
        /** @var array $config */
        $config = $container->get('config');
        $mailTransport = $container->get('database_mail_transport');

        return new MeetingService(
            $meetingMapper,
            $emReport,
            $config,
            $mailTransport
        );
    }
}
