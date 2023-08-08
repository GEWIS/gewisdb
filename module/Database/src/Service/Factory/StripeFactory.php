<?php

declare(strict_types=1);

namespace Database\Service\Factory;

use Database\Mapper\ActionLink as PaymentLinkMapper;
use Database\Mapper\CheckoutSession as PaymentMapper;
use Database\Service\Member as MemberService;
use Database\Service\Stripe as StripeService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Monolog\Logger;
use Psr\Container\ContainerInterface;

class StripeFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): StripeService {
        /** @var Logger $logger */
        $logger = $container->get('logger');
        /** @var PaymentLinkMapper $paymentLinkMapper */
        $paymentLinkMapper = $container->get(PaymentLinkMapper::class);
        /** @var PaymentMapper $paymentMapper */
        $paymentMapper = $container->get(PaymentMapper::class);
        /** @var MemberService $memberService */
        $memberService = $container->get(MemberService::class);
        /** @var array<string, string> $config */
        $config = $container->get('config')['stripe'];

        return new StripeService(
            $logger,
            $paymentLinkMapper,
            $paymentMapper,
            $memberService,
            $config,
        );
    }
}
