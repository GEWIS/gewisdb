<?php

declare(strict_types=1);

namespace Database\Service;

use Database\Mapper\ActionLink as PaymentLinkMapper;
use Database\Mapper\CheckoutSession as CheckoutSessionMapper;
use Database\Model\CheckoutSession as CheckoutSessionModel;
use Database\Model\Enums\CheckoutSessionStates;
use Database\Model\PaymentLink as PaymentLinkModel;
use Database\Model\ProspectiveMember as ProspectiveMemberModel;
use Database\Service\Member as MemberService;
use DateTime;
use DateTimeZone;
use Monolog\Logger;
use Stripe\Charge;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Refund;
use Stripe\StripeClient;
use Stripe\Webhook;
use UnexpectedValueException;

use function intval;
use function sprintf;
use function time;

class Stripe
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        private readonly Logger $logger,
        private readonly PaymentLinkMapper $paymentLinkMapper,
        private readonly CheckoutSessionMapper $checkoutSessionMapper,
        private readonly MemberService $memberService,
        private readonly array $config,
    ) {
    }

    public function getPaymentLink(string $token): ?PaymentLinkModel
    {
        return $this->paymentLinkMapper->findPaymentByToken($token);
    }

    /**
     * To ensure that we can check whether a Checkout Session and eventually the payment was successful we need to keep
     * track of what we are doing. As such, we create a `Payment` for the prospective member to track the Checkout
     * Session.
     *
     * Finally, we return the URL of the Stripe checkout page on which the user can pay.
     */
    public function getCheckoutLink(ProspectiveMemberModel $prospectiveMember): ?string
    {
        $session = $this->createCheckoutSession($prospectiveMember);

        if (null === $session) {
            return null;
        }

        $checkoutSession = new CheckoutSessionModel();
        $checkoutSession->setProspectiveMember($prospectiveMember);
        $checkoutSession->setCheckoutId($session->id);
        $checkoutSession->setCreated(
            DateTime::createFromFormat(
                'U',
                (string) $session->created,
            )->setTimezone(new DateTimeZone('Europe/Amsterdam')),
        );
        $checkoutSession->setExpiration(
            DateTime::createFromFormat(
                'U',
                (string) $session->expires_at,
            )->setTimezone(new DateTimeZone('Europe/Amsterdam')),
        );
        $this->checkoutSessionMapper->persist($checkoutSession);

        return $session->url;
    }

    /**
     * Get the id of a prospective member from the Checkout Session.
     */
    public function getLidnrFromCheckoutSession(string $sessionId): ?int
    {
        if (
            '' === $sessionId
            || 'null' === $sessionId
        ) {
            return null;
        }

        $checkoutSession = $this->getCheckoutSession($sessionId);

        if (null === $checkoutSession) {
            return null;
        }

        return intval($checkoutSession->client_reference_id);
    }

    /**
     * Try to restart an active Checkout Session, if this is not possible, create a new Checkout Session.
     */
    public function restartCheckoutLink(ProspectiveMemberModel $prospectiveMember): ?string
    {
        $lastCheckoutStub = $this->checkoutSessionMapper->findLatest($prospectiveMember);

        if (null === $lastCheckoutStub) {
            // We have a problem, a prospective member must have at least one checkout session associated with it.
            // Ignore it for now and create a new Checkout Session.
            return $this->getCheckoutLink($prospectiveMember);
        }

        // If the Checkout Session was recovered we want to go back to the original one (which has the correct
        // expiration and Recovery URL).
        $lastCheckoutStub = $lastCheckoutStub->getRecoveredFrom() ?? $lastCheckoutStub;

        // We have at least one known Checkout Session on file.
        if (
            CheckoutSessionStates::Paid === $lastCheckoutStub->getState()
            || CheckoutSessionStates::Pending === $lastCheckoutStub->getState()
        ) {
            // Checkout Session is finalised or will be after payment processing. Do not allow the prospective member to
            // do something else.
            return null;
        }

        if (CheckoutSessionStates::Failed === $lastCheckoutStub->getState()) {
            // Last payment failed, so we need to create a new Checkout Session for the user to be able to try again.
            return $this->getCheckoutLink($prospectiveMember);
        }

        if (CheckoutSessionStates::Expired === $lastCheckoutStub->getState()) {
            // The Checkout Session has already been abandoned.

            if (new DateTime() >= $lastCheckoutStub->getExpiration()) {
                // The Checkout Session is completely abandoned, as the maximum expiration for the recovery URL of 30
                // days has passed. Do NOT allow recovery of this session before scheduled deletion.

                if (null !== ($paymentLink = $prospectiveMember->getPaymentLink())) {
                    // Disable the payment link in case the prospective member tries again.
                    $paymentLink->setUsed(true);
                    $this->paymentLinkMapper->persist($paymentLink);
                }

                return null;
            }

            // The Checkout Session is not completely dead yet, so return the recovery URL.
            return $lastCheckoutStub->getRecoveryUrl();
        }

        // Checkout Session is still valid (at this point, not necessarily when the prospective member finally submits).
        // We try to retrieve the actual Checkout Session, if this succeeds we return the URL. If not we fail and let
        // the prospective member know.
        return $this->getCheckoutSession($lastCheckoutStub->getCheckoutId())?->url;
    }

    /**
     * Create a Checkout Session through the Stripe API. The Checkout Session has some required parameters, for more
     * information on their details see {@link https://stripe.com/docs/api/checkout/sessions/object}.
     */
    private function createCheckoutSession(ProspectiveMemberModel $prospectiveMember): ?CheckoutSession
    {
        try {
            return $this->getClient()->checkout->sessions->create([
                'line_items' => [
                    [
                        'price' => $this->config['membership_price_id'],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'payment_intent_data' => [
                    'statement_descriptor' => 'GEWIS Membership ' . $prospectiveMember->getLidnr(),
                ],
                'cancel_url' => $this->config['cancel_url'],
                'success_url' => $this->config['success_url'],
                'expires_at' => time() + 3600,
                'after_expiration' => [
                    'recovery' => [
                        'enabled' => true,
                    ],
                ],
                'client_reference_id' => $prospectiveMember->getLidnr(),
                'customer_email' => $prospectiveMember->getEmail(),
            ]);
        } catch (ApiErrorException $e) {
            // We must never throw, as this will break the enrolment flow, however, we do want to know what happened.
            $this->logger->error($e->getMessage() . ' ' . $e->getTraceAsString());
        }

        return null;
    }

    private function getCheckoutSession(string $sessionId): ?CheckoutSession
    {
        try {
            return $this->getClient()->checkout->sessions->retrieve($sessionId);
        } catch (ApiErrorException $e) {
            // We must never throw, as this will break the enrolment flow, however, we do want to know what happened.
            $this->logger->error($e->getMessage() . ' ' . $e->getTraceAsString());
        }

        return null;
    }

    /**
     * Create a refund for a prospective member. Returns `true` iff the Refund was successfully created.
     */
    public function createRefund(ProspectiveMemberModel $prospectiveMember): ?true
    {
        if (null !== ($checkoutSession = $this->checkoutSessionMapper->findLatest($prospectiveMember))) {
            try {
                // Get last PaymentIntent to obtain the latest Charge.
                $paymentIntent = $this->getClient()->paymentIntents->retrieve($checkoutSession->getPaymentIntentId());

                // Get the Charge.
                $charge = $paymentIntent->latest_charge;
                if ($charge instanceof Charge) {
                    $charge = $charge->id;
                }

                // Create a refund for the specific Charge.
                $this->getClient()->refunds->create(['charge' => $charge]);
            } catch (ApiErrorException $e) {
                // We must never throw, as this will break stuff, however, we do want to know what happened.
                $this->logger->error($e->getMessage() . ' ' . $e->getTraceAsString());

                return null;
            }

            // Send e-mail.
            $this->memberService->sendRegistrationUpdateEmail(
                $prospectiveMember,
                'refund-created',
            );

            return true;
        }

        return null;
    }

    /**
     * Determine whether there is already a Refund for a prospective member. This does say nothing about the state of
     * such a Refund, just that it exists.
     */
    public function hasRefund(ProspectiveMemberModel $prospectiveMember): ?bool
    {
        if (null !== ($checkoutSession = $this->checkoutSessionMapper->findLatest($prospectiveMember))) {
            try {
                return 0 !== $this->getClient()->refunds->all([
                    'payment_intent' => $checkoutSession->getPaymentIntentId(),
                    'limit' => 1,
                ])->count();
            } catch (ApiErrorException $e) {
                // We must never throw, as this will break stuff, however, we do want to know what happened.
                $this->logger->error($e->getMessage() . ' ' . $e->getTraceAsString());
            }
        }

        return null;
    }

    public function verifyEvent(
        string $content,
        string $signature,
    ): ?Event {
        try {
            return Webhook::constructEvent($content, $signature, $this->config['webhook_signing_key']);
        } catch (UnexpectedValueException) {
            // Malformed JSON payload.
            return null;
        } catch (SignatureVerificationException) {
            // Signature is not valid. Tampering?
            return null;
        }
    }

    /**
     * To keep track of how Checkout Sessions (and its associated payment) and Refunds evolve over time we need to be
     * able to handle a few webhook events that Stripe sends us. In other words, this is the "fulfillment" process.
     */
    public function handleEvent(Event $event): void
    {
        if ('charge.refund.updated' === $event->type) {
            /** @var Refund $refund */
            $refund = $event->data->object;
            $status = $refund->status;

            if (
                'failed' === $status
                || 'requires_action' === $status
                || 'canceled' === $status
            ) {
                // Send e-mail to secretary that there was an issue while processing the refund. They will have to ask
                // the ApplicatieBeheerCommissie and/or treasurer to determine the cause/what needs to happen to get it
                // resolved.
                $this->memberService->sendRefundProblemEmail($refund->id, $status);
            }

            return;
        }

        /** @var CheckoutSession $session */
        $session = $event->data->object;
        $storedCheckoutSession = $this->checkoutSessionMapper->findById($session->id);

        if (null === $storedCheckoutSession) {
            // The Checkout Session we store can only be null of the prospective member is already removed or when it
            // was recovered from an abandoned Checkout Session.

            if (null === $session->recovered_from) {
                // The Checkout Session was not recovered, so the only logical explanation is that the prospective
                // member is removed. We cannot process anything, so return.
                return;
            }

            // We are dealing with a recovered Checkout Session.
            $originalCheckoutSession = $this->checkoutSessionMapper->findById($session->recovered_from);

            if (null === $originalCheckoutSession) {
                // The original Checkout Session does not exist, the only logical explanation is that the prospective
                // member is removed.
                return;
            }

            // Check if we have previously recovered from the original Checkout Session.
            $recoveredBy = $this->checkoutSessionMapper->findRecoveredBy($originalCheckoutSession);

            if (null !== $recoveredBy) {
                // We have previously recovered and this is a new Checkout Session. This should not be possible.
                $this->logger->error(sprintf(
                    'Trying to recover Checkout Session % for the second time through %s.',
                    $originalCheckoutSession->getCheckoutId(),
                    $session->id,
                ));

                return;
            }

            // Create new Checkout Session for this recovery. Leave the state for it on 'CREATED', if something goes
            // wrong we can easily track what has happened.
            $storedCheckoutSession = new CheckoutSessionModel();
            $storedCheckoutSession->setProspectiveMember($originalCheckoutSession->getProspectiveMember());
            $storedCheckoutSession->setCheckoutId($session->id);
            $storedCheckoutSession->setCreated(DateTime::createFromFormat(
                'U',
                (string) $session->created,
            )->setTimezone(new DateTimeZone('Europe/Amsterdam')));
            $storedCheckoutSession->setExpiration(DateTime::createFromFormat(
                'U',
                (string) $session->expires_at,
            )->setTimezone(new DateTimeZone('Europe/Amsterdam')));
            // Link recovered Checkout Session to the old one.
            $storedCheckoutSession->setRecoveredFrom($originalCheckoutSession);

            $this->checkoutSessionMapper->persist($storedCheckoutSession);
        }

        // If this is `null` we are in a weird state, we have a checkout session but not a payment link. We tactfully
        // choose to ignore this.
        $paymentLink = $this->paymentLinkMapper->findPaymentByProspectiveMember(intval($session->client_reference_id));

        switch ($event->type) {
            case 'checkout.session.expired':
                // The prospective member did not complete the checkout within 24 hours. We mark the stored checkout
                // session as expired.
                $storedCheckoutSession->setState(CheckoutSessionStates::Expired);

                if (null !== $session->after_expiration) {
                    // We are handling the expiration of the very first Checkout Session of the prospective member. The
                    // Recovery URL is valid for 30 days.
                    $storedCheckoutSession->setExpiration(DateTime::createFromFormat(
                        'U',
                        (string) $session->after_expiration->recovery->expires_at,
                    )->setTimezone(new DateTimeZone('Europe/Amsterdam')));
                    $storedCheckoutSession->setRecoveryUrl($session->after_expiration->recovery->url);
                }
                // (re)set the used state of the payment link to enable it.
                $paymentLink?->setUsed(false);

                // Save changes before sending e-mail. This ensures we do not lose information if the e-mail fails.
                $this->checkoutSessionMapper->persist($storedCheckoutSession);

                // Send e-mail, as this event only happens once after 24 hours for the original Checkout Session, we do
                // not have to keep track of if/when we may have already sent an e-mail in the past.
                $this->memberService->sendRegistrationUpdateEmail(
                    $storedCheckoutSession->getProspectiveMember(),
                    'checkout-expired',
                );

                break;
            case 'checkout.session.completed':
                // The prospective member has completed the checkout but the payment may be delayed. If the payment is
                // not delayed we directly mark the stored checkout session as 'PAID', otherwise it will be 'PENDING'.
                if ('paid' === $session->payment_status) {
                    $storedCheckoutSession->setState(CheckoutSessionStates::Paid);
                    $storedCheckoutSession->setPaymentIntentId($session->payment_intent);
                } else {
                    $storedCheckoutSession->setState(CheckoutSessionStates::Pending);
                }

                // Either way, the payment link should not be active.
                $paymentLink?->setUsed(true);

                break;
            case 'checkout.session.async_payment_succeeded':
                // A delayed payment has succeeded. So we mark the stored checkout session as 'PAID'.
                $storedCheckoutSession->setState(CheckoutSessionStates::Paid);
                $storedCheckoutSession->setPaymentIntentId($session->payment_intent);
                $paymentLink?->setUsed(true);

                break;
            case 'checkout.session.async_payment_failed':
                // A delayed payment has failed.
                $storedCheckoutSession->setState(CheckoutSessionStates::Failed);
                $paymentLink?->setUsed(false);

                // Save changes before sending e-mail. This ensures we do not lose information if the e-mail fails.
                $this->checkoutSessionMapper->persist($storedCheckoutSession);

                // Send e-mail.
                $this->memberService->sendRegistrationUpdateEmail(
                    $storedCheckoutSession->getProspectiveMember(),
                    'checkout-failed',
                );

                break;
            default:
                // Unknown event type.
                break;
        }

        if (null !== $paymentLink) {
            $this->paymentLinkMapper->persist($paymentLink);
        }

        $this->checkoutSessionMapper->persist($storedCheckoutSession);
    }

    /**
     * Get the Stripe client. This should never be directly accessible, helper functions will handle required actions to
     * prevent unwanted access.
     */
    private function getClient(): StripeClient
    {
        return new StripeClient([
            'api_key' => $this->config['secret_key'],
            'stripe_version' => $this->config['api_version'],
        ]);
    }
}
