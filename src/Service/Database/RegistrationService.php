<?php

declare(strict_types=1);

namespace App\Service\Database;

use App\Entity\Database\MailingList;
use App\Entity\Database\ProspectiveMember as ProspectiveMemberModel;
use App\Repository\Database\MailingListRepository;
use App\Repository\Database\ProspectiveMemberRepository;
use App\Service\Database\Member as MemberService;
use Psr\Clock\ClockInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * The public sign-up flow: everything between the registration form and a prospective member who is on their way to
 * the checkout.
 */
class RegistrationService
{
    /**
     * The department's own network, from which enrolment can be vouched for in person.
     */
    private const string MCS_NETWORK = '131.155.68.0/22';

    public function __construct(
        private readonly MailingListRepository $mailingListRepository,
        private readonly ProspectiveMemberRepository $prospectiveMemberRepository,
        private readonly MemberService $memberService,
        private readonly StripeService $stripeService,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * During July an enrolment at the TU/e cannot be verified, so registration is closed to everyone who is not
     * standing in the department.
     */
    public function isOpen(?string $clientIp): bool
    {
        if (7 !== (int) $this->clock->now()->format('n')) {
            return true;
        }

        return null !== $clientIp
            && IpUtils::checkIp(
                $clientIp,
                self::MCS_NETWORK,
            );
    }

    /**
     * The mailing lists the registration form offers.
     *
     * @return MailingList[]
     */
    public function getMailingListsOnForm(): array
    {
        return $this->mailingListRepository->findAllOnForm();
    }

    /**
     * Register a prospective member from the submitted registration form and return the URL of their checkout page.
     *
     * Returns `null` when the registration was rejected (the form carries the reason) or when no checkout page could
     * be created for it.
     */
    public function register(FormInterface $form): ?string
    {
        $prospectiveMember = $this->memberService->subscribe($form);

        if (null === $prospectiveMember) {
            return null;
        }

        // Always sent, and sent before the checkout is created: it contains a payment link of its own, which is the
        // only way back into the flow if the checkout does not come up.
        $this->memberService->sendRegistrationUpdateEmail(
            $prospectiveMember,
            'registration',
        );

        return $this->stripeService->getCheckoutLink($prospectiveMember);
    }

    /**
     * Find the prospective member that Stripe sent back to us, if the Checkout Session is still known.
     */
    public function getProspectiveMemberByCheckoutSession(string $checkoutSessionId): ?ProspectiveMemberModel
    {
        $lidnr = $this->stripeService->getLidnrFromCheckoutSession($checkoutSessionId);

        if (null === $lidnr) {
            return null;
        }

        return $this->prospectiveMemberRepository->find($lidnr);
    }

    /**
     * Send a prospective member back to the checkout with their payment link, returning the URL to continue at.
     */
    public function restartCheckout(string $token): string|CheckoutRestartFailure
    {
        $paymentLink = $this->stripeService->getPaymentLink($token);

        if (
            null === $paymentLink
            || $paymentLink->isUsed()
        ) {
            return CheckoutRestartFailure::LinkUnusable;
        }

        return $this->stripeService->restartCheckoutLink($paymentLink->getProspectiveMember())
            ?? CheckoutRestartFailure::CheckoutUnavailable;
    }

    /**
     * Remove a prospective member, refunding the membership fee first if it was already paid.
     */
    public function removeProspectiveMember(ProspectiveMemberModel $prospectiveMember): ProspectiveMemberRemoval
    {
        if (!$prospectiveMember->canBeDeleted()) {
            return ProspectiveMemberRemoval::NotRemovable;
        }

        if ($prospectiveMember->hasPaid()) {
            $hasRefund = $this->stripeService->hasRefund($prospectiveMember);

            if (null === $hasRefund) {
                return ProspectiveMemberRemoval::RefundStatusUnknown;
            }

            if (
                !$hasRefund
                && null === $this->stripeService->createRefund($prospectiveMember)
            ) {
                return ProspectiveMemberRemoval::RefundFailed;
            }
        }

        // Only now: a prospective member who is no longer on file can no longer be refunded.
        $this->memberService->removeProspective($prospectiveMember);

        return ProspectiveMemberRemoval::Removed;
    }
}
