<?php

declare(strict_types=1);

namespace Database\Controller\Factory;

use Database\Controller\ProspectiveMemberController;
use Database\Service\Member as MemberService;
use Database\Service\Stripe as StripeService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class ProspectiveMemberControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ProspectiveMemberController {
        /** @var MvcTranslator $translator */
        $translator = $container->get(MvcTranslator::class);
        /** @var MemberService $memberService */
        $memberService = $container->get(MemberService::class);
        /** @var StripeService $stripeService */
        $stripeService = $container->get(StripeService::class);

        return new ProspectiveMemberController(
            $translator,
            $memberService,
            $stripeService,
        );
    }
}
