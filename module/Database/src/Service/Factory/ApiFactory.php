<?php

declare(strict_types=1);

namespace Database\Service\Factory;

use Database\Service\Api as ApiService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Report\Mapper\Member as ReportMemberMapper;

class ApiFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ApiService {
        /** @var ReportMemberMapper $reportMemberMapper */
        $reportMemberMapper = $container->get(ReportMemberMapper::class);

        return new ApiService($reportMemberMapper);
    }
}
