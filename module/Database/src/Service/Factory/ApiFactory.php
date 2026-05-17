<?php

declare(strict_types=1);

namespace Database\Service\Factory;

use Application\Service\Config as ConfigService;
use Database\Service\Api as ApiService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;
use Report\Mapper\Member as ReportMemberMapper;

class ApiFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ApiService {
        $reportMemberMapper = $container->get(ReportMemberMapper::class);
        $configService = $container->get(ConfigService::class);

        return new ApiService(
            $reportMemberMapper,
            $configService,
        );
    }
}
