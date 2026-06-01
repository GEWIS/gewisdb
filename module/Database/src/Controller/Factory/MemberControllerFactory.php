<?php

declare(strict_types=1);

namespace Database\Controller\Factory;

use Checker\Service\Checker as CheckerService;
use Database\Controller\MemberController;
use Database\Service\MailingList as MailingListService;
use Database\Service\Member as MemberService;
use Database\Service\Stripe as StripeService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class MemberControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MemberController {
        /** @var MvcTranslator $translator */
        $translator = $container->get(MvcTranslator::class);
        /** @var CheckerService $checkerService */
        $checkerService = $container->get(CheckerService::class);
        /** @var MailingListService $mailingListService */
        $mailingListService = $container->get(MailingListService::class);
        /** @var MemberService $memberService */
        $memberService = $container->get(MemberService::class);
        /** @var StripeService $stripeService */
        $stripeService = $container->get(StripeService::class);
        /** @var string $remoteAddress */
        $remoteAddress = $container->get('database_remoteaddress');

        return new MemberController(
            $translator,
            $checkerService,
            $mailingListService,
            $memberService,
            $stripeService,
            $remoteAddress,
        );
    }
}
