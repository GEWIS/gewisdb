<?php

declare(strict_types=1);

namespace App\Tests\Service\Database;

use App\Entity\Database\CheckoutSession;
use App\Entity\Database\Enums\CheckoutSessionStates;
use App\Entity\Database\PaymentLink;
use App\Entity\Database\ProspectiveMember;
use App\Repository\Database\ActionLinkRepository;
use App\Repository\Database\CheckoutSessionRepository;
use App\Service\Database\Member as MemberService;
use App\Service\Database\StripeService;
use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * What the checkout decides before it talks to Stripe.
 *
 * Everything that reaches the API is left alone here: the client is built inside the service, so those paths cannot
 * be exercised without a network. The decisions in front of them can, and they are the ones that decide whether
 * somebody is sent back to a checkout, kept away from one, or charged twice.
 */
#[CoversClass(StripeService::class)]
class StripeServiceTest extends TestCase
{
    /**
     * A finished or in-flight payment is not something to reopen: it would be a second chance to pay for the same
     * membership.
     */
    #[DataProvider('statesThatMayNotBeReopened')]
    public function testRefusesToReopenACheckoutThatIsFinishedOrUnderWay(CheckoutSessionStates $state): void
    {
        $prospectiveMember = new ProspectiveMember();

        self::assertNull(
            $this->service($this->checkoutSession($state))->restartCheckoutLink($prospectiveMember),
        );
    }

    /**
     * @return array<string, array{CheckoutSessionStates}>
     */
    public static function statesThatMayNotBeReopened(): array
    {
        return [
            'paid for' => [CheckoutSessionStates::Paid],
            'payment being processed' => [CheckoutSessionStates::Pending],
        ];
    }

    /**
     * Stripe keeps a recovery URL for 30 days; while it lasts, that is where someone is sent back to.
     */
    public function testSendsSomeoneBackToTheRecoveryUrlOfAnAbandonedCheckout(): void
    {
        $session = $this->checkoutSession(CheckoutSessionStates::Expired, '+5 days');
        $session->setRecoveryUrl('https://checkout.stripe.test/recover');

        self::assertSame(
            'https://checkout.stripe.test/recover',
            $this->service($session)->restartCheckoutLink(new ProspectiveMember()),
        );
    }

    /**
     * Once the recovery URL is dead the payment link that leads to it is burned as well, so a second attempt does
     * not arrive at a page that cannot work.
     */
    public function testBurnsThePaymentLinkOfACheckoutThatIsPastRecovery(): void
    {
        $prospectiveMember = new ProspectiveMember();
        $paymentLink = new PaymentLink();
        $paymentLink->setProspectiveMember($prospectiveMember);
        $prospectiveMember->setPaymentLink($paymentLink);

        $actionLinkRepository = $this->createMock(ActionLinkRepository::class);
        $actionLinkRepository->expects(self::once())
            ->method('persist')
            ->with($paymentLink);

        $service = $this->service(
            $this->checkoutSession(CheckoutSessionStates::Expired, '-1 day'),
            $actionLinkRepository,
        );

        self::assertNull($service->restartCheckoutLink($prospectiveMember));
        self::assertTrue($paymentLink->isUsed());
    }

    /**
     * A recovered session points back at the one it came from, which is the session with the usable dates.
     */
    public function testFollowsARecoveredCheckoutBackToTheOneItCameFrom(): void
    {
        $original = $this->checkoutSession(CheckoutSessionStates::Expired, '+5 days');
        $original->setRecoveryUrl('https://checkout.stripe.test/original');

        $recovered = $this->checkoutSession(CheckoutSessionStates::Created, '+5 days');
        $recovered->setRecoveredFrom($original);

        self::assertSame(
            'https://checkout.stripe.test/original',
            $this->service($recovered)->restartCheckoutLink(new ProspectiveMember()),
        );
    }

    /**
     * The session id arrives in a URL, where "no session" can turn into the four letters that spell it.
     */
    #[DataProvider('sessionIdsThatAreNotOne')]
    public function testTakesNoSessionIdAsNoSession(string $sessionId): void
    {
        self::assertNull($this->service()->getLidnrFromCheckoutSession($sessionId));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function sessionIdsThatAreNotOne(): array
    {
        return [
            'empty' => [''],
            'the word null' => ['null'],
        ];
    }

    public function testCannotTellWhetherSomeoneWasRefundedWithoutAPaymentToLookUp(): void
    {
        $prospectiveMember = new ProspectiveMember();

        self::assertNull($this->service()->hasRefund($prospectiveMember));

        $withoutPaymentIntent = $this->checkoutSession(CheckoutSessionStates::Paid);

        self::assertNull($this->service($withoutPaymentIntent)->hasRefund($prospectiveMember));
    }

    private function checkoutSession(
        CheckoutSessionStates $state,
        string $expiration = '+1 day',
    ): CheckoutSession {
        $session = new CheckoutSession();
        $session->setCheckoutId('cs_test');
        $session->setState($state);
        $session->setCreated(new DateTime('-1 hour'));
        $session->setExpiration(new DateTime($expiration));

        return $session;
    }

    private function service(
        ?CheckoutSession $lastSession = null,
        ?ActionLinkRepository $actionLinkRepository = null,
    ): StripeService {
        $checkoutSessionRepository = self::createStub(CheckoutSessionRepository::class);
        $checkoutSessionRepository->method('findLatest')->willReturn($lastSession);

        return new StripeService(
            self::createStub(LoggerInterface::class),
            $actionLinkRepository ?? self::createStub(ActionLinkRepository::class),
            $checkoutSessionRepository,
            self::createStub(MemberService::class),
            '2024-06-20',
            'sk_test',
            'whsec_test',
            'price_test',
            'https://join.gewis.test/cancel',
            'https://join.gewis.test/success',
        );
    }
}
