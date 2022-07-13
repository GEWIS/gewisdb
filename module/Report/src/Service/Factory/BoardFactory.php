<?php

namespace Report\Service\Factory;

use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;
use Report\Service\Board as BoardService;
use Zend\ServiceManager\Factory\FactoryInterface;

class BoardFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return BoardService
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): BoardService {
        /** @var EntityManager $emReport */
        $emReport = $container->get('doctrine.entitymanager.orm_report');

        return new BoardService($emReport);
    }
}
