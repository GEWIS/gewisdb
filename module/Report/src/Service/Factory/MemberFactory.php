<?php

namespace Report\Service\Factory;

use Database\Mapper\Member as MemberMapper;
use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;
use Report\Service\Member as MemberService;
use Laminas\ServiceManager\Factory\FactoryInterface;

class MemberFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return MemberService
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): MemberService {
        /** @var MemberMapper $memberMapper */
        $memberMapper = $container->get(MemberMapper::class);
        /** @var EntityManager $emReport */
        $emReport = $container->get('doctrine.entitymanager.orm_report');

        return new MemberService(
            $memberMapper,
            $emReport
        );
    }
}
