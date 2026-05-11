<?php

declare(strict_types=1);

namespace Report\Service\Factory;

use Doctrine\ORM\EntityManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Report\Service\Board as BoardService;
use Report\Service\Keyholder as KeyholderService;
use Report\Service\Organ as OrganService;
use Report\Service\SubDecision as SubDecisionService;

class SubDecisionFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): SubDecisionService {
        /** @var BoardService $boardService */
        $boardService = $container->get(BoardService::class);
        /** @var KeyholderService $keyholderService */
        $keyholderService = $container->get(KeyholderService::class);
        /** @var OrganService $organService */
        $organService = $container->get(OrganService::class);
        /** @var EntityManager $emReport */
        $emReport = $container->get('doctrine.entitymanager.orm_report');

        return new SubDecisionService(
            $emReport,
            $boardService,
            $keyholderService,
            $organService,
        );
    }
}
