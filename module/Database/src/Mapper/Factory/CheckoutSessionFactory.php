<?php

declare(strict_types=1);

namespace Database\Mapper\Factory;

use Database\Mapper\CheckoutSession as PaymentMapper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class CheckoutSessionFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): PaymentMapper {
        return new PaymentMapper($container->get('doctrine.entitymanager.orm_default'));
    }
}
