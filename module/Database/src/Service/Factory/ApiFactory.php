<?php

declare(strict_types=1);

namespace Database\Service\Factory;

use Database\Mapper\Member as MemberMapper;
use Database\Service\Api as ApiService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ApiFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return ApiService
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): ApiService {
        /** @var MemberMapper $memberMapper */
        $memberMapper = $container->get(MemberMapper::class);

        return new ApiService(
            $memberMapper,
        );
    }
}
