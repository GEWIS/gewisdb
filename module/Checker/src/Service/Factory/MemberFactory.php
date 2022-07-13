<?php

namespace Checker\Service\Factory;

use Checker\Mapper\Member as MemberMapper;
use Checker\Service\Member as MemberService;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

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
        /** @var array $config */
        $config = $container->get('config');

        return new MemberService(
            $memberMapper,
            $config
        );
    }
}
