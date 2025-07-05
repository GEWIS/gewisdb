<?php

declare(strict_types=1);

namespace Database\Controller\Factory;

use Checker\Service\Checker as CheckerService;
use Database\Controller\MemberController;
use Database\Service\Mailman as MailmanService;
use Database\Service\Member as MemberService;
use Database\Service\Stripe as StripeService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class MemberControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MemberController {
        /** @var MvcTranslator $translator */
        $translator = $container->get(MvcTranslator::class);
        /** @var CheckerService $checkerService */
        $checkerService = $container->get(CheckerService::class);
        /** @var MailmanService $mailmanService */
        $mailmanService = $container->get(MailmanService::class);
        /** @var MemberService $memberService */
        $memberService = $container->get(MemberService::class);
        /** @var StripeService $stripeService */
        $stripeService = $container->get(StripeService::class);
        /** @var string $remoteAddress */
        $remoteAddress = $container->get('database_remoteaddress');

        return new MemberController(
            $translator,
            $checkerService,
            $mailmanService,
            $memberService,
            $stripeService,
            $remoteAddress,
        );
    }
}
