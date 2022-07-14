<?php

namespace Report\Service\Factory;

use Database\Mapper\MailingList as MailingListMapper;
use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;
use Report\Service\Misc as MiscService;
use Laminas\ServiceManager\Factory\FactoryInterface;

class MiscFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return MiscService
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): MiscService {
        /** @var MailingListMapper $mailingListMapper */
        $mailingListMapper = $container->get(MailingListMapper::class);
        /** @var EntityManager $emReport */
        $emReport = $container->get('doctrine.entitymanager.orm_report');

        return new MiscService(
            $mailingListMapper,
            $emReport
        );
    }
}
