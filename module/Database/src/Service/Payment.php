<?php

declare(strict_types=1);

namespace Database\Service;

use Database\Mapper\ActionLink as PaymentLinkMapper;
use Database\Mapper\CheckoutSession as CheckoutSessionMapper;
use Database\Model\CheckoutSession as CheckoutSessionModel;
use Database\Model\PaymentLink as PaymentLinkModel;
use Database\Model\ProspectiveMember as ProspectiveMemberModel;
use DateInterval;
use DateTime;
use Monolog\Logger;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use UnexpectedValueException;

use function intval;

class Payment
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        private readonly Logger $logger,
        private readonly PaymentLinkMapper $paymentLinkMapper,
        private readonly CheckoutSessionMapper $checkoutSessionMapper,
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
        $checkoutSession = $this->createCheckoutSession($prospectiveMember);

        if (null === $checkoutSession) {
            return null;
        }

        $payment = new CheckoutSessionModel();
        $payment->setProspectiveMember($prospectiveMember);
        $payment->setCheckoutId($checkoutSession->id);
        $payment->setExpiration(DateTime::createFromFormat('U', (string) $checkoutSession->expires_at));
        $this->checkoutSessionMapper->persist($payment);

        return $checkoutSession->url;
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

        // We have at least one know checkout session on file.
        if ((new DateTime())->sub(new DateInterval('PT5M')) >= $lastCheckoutStub->getExpiration()) {
            // Checkout Session has expired or is about to expire (this will create a webhook event to fix some stuff on
            // this payment record). As prospective members may be a bit slow, we internally expire a Checkout Session
            // five minutes earlier. We do not want to deal with people who are in the middle of a checkout when it
            // expires. Hence, we already create a new Checkout Session.
            return $this->getCheckoutLink($prospectiveMember);
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
                'cancel_url' => $this->config['cancel_url'],
                'success_url' => $this->config['success_url'],
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
     * To keep track of how the Checkout Session and its associated payment evolves over time we need to be able to
     * handle a few events from webhooks that Stripe sends us. In other words, this is the fulfillment process.
     */
    public function handleEvent(Event $event): void
    {
        /** @var CheckoutSession $session */
        $session = $event->data->object;
        $storedCheckoutSession = $this->checkoutSessionMapper->findById($session->id);

        if (null === $storedCheckoutSession) {
            // The checkout session we store can only be null of the prospective member is already removed. We do cannot
            // process anything, so return.
            return;
        }

        // If this is `null` we are in a weird state, we have a checkout session but not a payment link. We tactfully
        // choose to ignore this.
        $paymentLink = $this->paymentLinkMapper->findPaymentByProspectiveMember(intval($session->client_reference_id));

        switch ($event->type) {
            case 'checkout.session.expired':
                // The prospective member did not complete the checkout within 24 hours. We mark the stored checkout
                // session as expired and (re)set the used state of the payment link to enable it.
                $storedCheckoutSession->setState(CheckoutSessionModel::EXPIRED);
                $paymentLink?->setUsed(false);

                // TODO: Send AT MOST 1 reminder to pay.

                break;
            case 'checkout.session.completed':
                // The prospective member has completed the checkout but the payment may be delayed. If the payment is
                // not delayed we directly mark the stored checkout session as 'PAID', otherwise it will be 'PENDING'.
                if ('paid' === $session->payment_status) {
                    $storedCheckoutSession->setState(CheckoutSessionModel::PAID);
                } else {
                    $storedCheckoutSession->setState(CheckoutSessionModel::PENDING);
                }

                // Either way, the payment link should not be active.
                $paymentLink?->setUsed(true);

                break;
            case 'checkout.session.async_payment_succeeded':
                // A delayed payment has succeeded. So we mark the stored checkout session as 'PAID'.
                $storedCheckoutSession->setState(CheckoutSessionModel::PAID);
                $paymentLink?->setUsed(true);

                break;
            case 'checkout.session.async_payment_failed':
                // A delayed payment has failed.
                $storedCheckoutSession->setState(CheckoutSessionModel::FAILED);
                $paymentLink?->setUsed(false);

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
