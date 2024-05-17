<?php

declare(strict_types=1);

namespace Database\Mapper\Factory;

use Database\Mapper\CheckoutSession as PaymentMapper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class CheckoutSessionFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): PaymentMapper {
        return new PaymentMapper($container->get('doctrine.entitymanager.orm_default'));
    }
}
