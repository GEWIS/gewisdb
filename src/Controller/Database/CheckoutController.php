<?php

declare(strict_types=1);

namespace App\Controller\Database;

use App\Service\Database\CheckoutRestartFailure;
use App\Service\Database\RegistrationService;
use App\Service\Database\StripeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function is_string;

/**
 * Where the payment provider drops a prospective member off: the pages they return to after the checkout, the link
 * that puts them back on it, and the webhook that tells us what actually happened.
 */
final class CheckoutController extends AbstractController
{
    public function __construct(
        private readonly RegistrationService $registrationService,
        private readonly StripeService $stripeService,
    ) {
    }

    #[Route(
        path: '/checkout/completed',
        name: 'join_checkout_completed_short',
        methods: ['GET'],
    )]
    #[Route(
        path: '/member/subscribe/checkout/completed',
        name: 'join_checkout_completed',
        methods: ['GET'],
    )]
    public function completed(Request $request): Response
    {
        return $this->renderStatus(
            'completed',
            $request,
        );
    }

    #[Route(
        path: '/checkout/cancelled',
        name: 'join_checkout_cancelled_short',
        methods: ['GET'],
    )]
    #[Route(
        path: '/member/subscribe/checkout/cancelled',
        name: 'join_checkout_cancelled',
        methods: ['GET'],
    )]
    public function cancelled(Request $request): Response
    {
        return $this->renderStatus(
            'cancelled',
            $request,
        );
    }

    #[Route(
        path: '/checkout/error',
        name: 'join_checkout_error_short',
        methods: ['GET'],
    )]
    #[Route(
        path: '/member/subscribe/checkout/error',
        name: 'join_checkout_error',
        methods: ['GET'],
    )]
    public function error(Request $request): Response
    {
        return $this->renderStatus(
            'failed',
            $request,
        );
    }

    #[Route(
        path: '/checkout/restart/{token}',
        name: 'join_checkout_restart_short',
        requirements: ['token' => '[a-zA-Z0-9_\-+]+'],
        methods: ['GET'],
    )]
    #[Route(
        path: '/member/subscribe/checkout/restart/{token}',
        name: 'join_checkout_restart',
        requirements: ['token' => '[a-zA-Z0-9_\-+]+'],
        methods: ['GET'],
    )]
    public function restart(string $token): Response
    {
        $restart = $this->registrationService->restartCheckout($token);

        if (is_string($restart)) {
            return $this->redirect(
                $restart,
                Response::HTTP_SEE_OTHER,
            );
        }

        return $this->render(
            'join/checkout-restart.html.twig',
            [
                'error' => CheckoutRestartFailure::CheckoutUnavailable === $restart,
            ],
        );
    }

    /**
     * Stripe calls this server-to-server, without a session and without a token: the signature over the body is the
     * only thing that says the call is theirs, so nothing else happens until it has been verified.
     */
    #[Route(
        path: '/checkout/webhook',
        name: 'join_checkout_webhook_short',
        methods: ['POST'],
    )]
    #[Route(
        path: '/member/subscribe/checkout/webhook',
        name: 'join_checkout_webhook',
        methods: ['POST'],
    )]
    public function webhook(Request $request): Response
    {
        $accepted = $this->stripeService->handleWebhook(
            $request->getContent(),
            $request->headers->get('Stripe-Signature'),
        );

        return new Response(status: $accepted ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);
    }

    /**
     * The prospective member is identified by the Checkout Session that Stripe puts in the return URL; it is not
     * known when they arrive here by hand or when the session has since gone.
     */
    private function renderStatus(
        string $status,
        Request $request,
    ): Response {
        $prospectiveMember = $this->registrationService->getProspectiveMemberByCheckoutSession(
            (string) $request->query->get(
                'stripe_session_id',
                '',
            ),
        );
        $token = $prospectiveMember?->getPaymentLink()?->getToken();

        return $this->render(
            'join/checkout-status.html.twig',
            [
                'status' => $status,
                'first_name' => $prospectiveMember?->getFirstName(),
                'restart_url' => null === $token
                    ? null
                    : $this->generateUrl(
                        'join_checkout_restart',
                        ['token' => $token],
                    ),
            ],
        );
    }
}
