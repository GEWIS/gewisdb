<?php

declare(strict_types=1);

namespace App\Tests\Service\Database;

use App\Entity\Database\PaymentLink;
use App\Entity\Database\ProspectiveMember;
use App\Entity\Database\ProspectiveMember as ProspectiveMemberModel;
use App\Repository\Database\MailingListRepository;
use App\Repository\Database\ProspectiveMemberRepository;
use App\Service\Database\CheckoutRestartFailure;
use App\Service\Database\Member as MemberService;
use App\Service\Database\ProspectiveMemberRemoval;
use App\Service\Database\RegistrationService;
use App\Repository\Database\ActionLinkRepository;
use App\Repository\Database\CheckoutSessionRepository;
use App\Service\Database\StripeService;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Override;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Form\FormInterface;

/**
 * The public sign-up flow, where a registration turns into money and an e-mail that is the only way back into it.
 */
#[CoversClass(RegistrationService::class)]
class RegistrationServiceTest extends TestCase
{
    private MemberService $memberService;

    private StripeService $stripeService;

    /**
     * Enrolment at the TU/e cannot be verified in July, so the form closes to everyone who is not standing in the
     * department.
     */
    #[DataProvider('momentsAndAddresses')]
    public function testIsOnlyOpenInJulyToTheDepartmentsOwnNetwork(
        string $now,
        ?string $clientIp,
        bool $open,
    ): void {
        self::assertSame(
            $open,
            $this->service($now)->isOpen($clientIp),
        );
    }

    /**
     * @return array<string, array{string, string|null, bool}>
     */
    public static function momentsAndAddresses(): array
    {
        return [
            'August, from anywhere' => ['2026-08-20', '203.0.113.10', true],
            'August, without an address' => ['2026-08-20', null, true],
            'June, the day before it closes' => ['2026-06-30', '203.0.113.10', true],
            'July, from the internet' => ['2026-07-15', '203.0.113.10', false],
            'July, from the department' => ['2026-07-15', '131.155.68.1', true],
            'July, from the far end of the department' => ['2026-07-15', '131.155.71.254', true],
            'July, from next door' => ['2026-07-15', '131.155.72.1', false],
            'July, without an address' => ['2026-07-15', null, false],
            'August again' => ['2026-08-01', null, true],
        ];
    }

    public function testSendsTheRegistrationEmailBeforeSendingAnyoneToTheCheckout(): void
    {
        $prospectiveMember = new ProspectiveMember();
        $order = [];

        $memberService = $this->createMock(MemberService::class);
        $memberService->method('subscribe')->willReturn($prospectiveMember);
        $memberService->expects(self::once())
            ->method('sendRegistrationUpdateEmail')
            ->with($prospectiveMember, 'registration')
            ->willReturnCallback(static function () use (&$order): void {
                $order[] = 'mail';
            });
        $this->memberService = $memberService;

        $stripeService = $this->createMock(StripeService::class);
        $stripeService->expects(self::once())
            ->method('getCheckoutLink')
            ->willReturnCallback(static function () use (&$order): string {
                $order[] = 'checkout';

                return 'https://checkout.stripe.test/session';
            });
        $this->stripeService = $stripeService;

        $url = $this->service()->register(self::createStub(FormInterface::class));

        self::assertSame('https://checkout.stripe.test/session', $url);
        self::assertSame(['mail', 'checkout'], $order);
    }

    /**
     * The e-mail carries a payment link of its own, so it is worth having even when the checkout does not come up.
     */
    public function testStillSendsTheEmailWhenNoCheckoutPageCanBeMade(): void
    {
        $memberService = $this->createMock(MemberService::class);
        $memberService->method('subscribe')->willReturn(new ProspectiveMember());
        $memberService->expects(self::once())->method('sendRegistrationUpdateEmail');
        $this->memberService = $memberService;

        $this->stripeService = self::createStub(StripeService::class);
        $this->stripeService->method('getCheckoutLink')->willReturn(null);

        self::assertNull($this->service()->register(self::createStub(FormInterface::class)));
    }

    public function testARejectedRegistrationIsNeitherMailedNorCharged(): void
    {
        $memberService = $this->createMock(MemberService::class);
        $memberService->method('subscribe')->willReturn(null);
        $memberService->expects(self::never())->method('sendRegistrationUpdateEmail');
        $this->memberService = $memberService;

        $stripeService = $this->createMock(StripeService::class);
        $stripeService->expects(self::never())->method('getCheckoutLink');
        $this->stripeService = $stripeService;

        self::assertNull($this->service()->register(self::createStub(FormInterface::class)));
    }

    public function testSendsSomeoneBackToTheCheckoutWithTheirPaymentLink(): void
    {
        $this->stripeService = self::createStub(StripeService::class);
        $this->stripeService->method('getPaymentLink')->willReturn($this->paymentLink());
        $this->stripeService->method('restartCheckoutLink')->willReturn('https://checkout.stripe.test/again');

        self::assertSame('https://checkout.stripe.test/again', $this->service()->restartCheckout('a-token'));
    }

    public function testRefusesAPaymentLinkThatIsUnknownOrAlreadyUsed(): void
    {
        $used = $this->paymentLink();
        $used->setUsed(true);

        $this->stripeService = self::createStub(StripeService::class);
        $this->stripeService->method('getPaymentLink')->willReturn(null);

        self::assertSame(CheckoutRestartFailure::LinkUnusable, $this->service()->restartCheckout('unknown'));

        $this->stripeService = self::createStub(StripeService::class);
        $this->stripeService->method('getPaymentLink')->willReturn($used);

        self::assertSame(CheckoutRestartFailure::LinkUnusable, $this->service()->restartCheckout('used'));
    }

    public function testReportsACheckoutThatCannotBeReopened(): void
    {
        $this->stripeService = self::createStub(StripeService::class);
        $this->stripeService->method('getPaymentLink')->willReturn($this->paymentLink());
        $this->stripeService->method('restartCheckoutLink')->willReturn(null);

        self::assertSame(CheckoutRestartFailure::CheckoutUnavailable, $this->service()->restartCheckout('a-token'));
    }

    /**
     * Money first: a prospective member who is off the books can no longer be refunded, so nothing is removed until
     * the refund is settled.
     */
    public function testRefundsBeforeRemovingSomeoneWhoHadAlreadyPaid(): void
    {
        $prospectiveMember = $this->paidProspectiveMember();
        $order = [];

        $stripeService = $this->createMock(StripeService::class);
        $stripeService->method('hasRefund')->willReturn(false);
        $stripeService->expects(self::once())
            ->method('createRefund')
            ->willReturnCallback(static function () use (&$order): true {
                $order[] = 'refund';

                return true;
            });
        $this->stripeService = $stripeService;

        $memberService = $this->createMock(MemberService::class);
        $memberService->expects(self::once())
            ->method('removeProspective')
            ->willReturnCallback(static function () use (&$order): void {
                $order[] = 'remove';
            });
        $this->memberService = $memberService;

        self::assertSame(
            ProspectiveMemberRemoval::Removed,
            $this->service()->removeProspectiveMember($prospectiveMember),
        );
        self::assertSame(['refund', 'remove'], $order);
    }

    public function testKeepsSomeoneWhoseRefundCouldNotBeMadeOrChecked(): void
    {
        $prospectiveMember = $this->paidProspectiveMember();

        $memberService = $this->createMock(MemberService::class);
        $memberService->expects(self::never())->method('removeProspective');
        $this->memberService = $memberService;

        $this->stripeService = self::createStub(StripeService::class);
        $this->stripeService->method('hasRefund')->willReturn(null);

        self::assertSame(
            ProspectiveMemberRemoval::RefundStatusUnknown,
            $this->service()->removeProspectiveMember($prospectiveMember),
        );

        $this->stripeService = $this->stripeThatCannotRefund();

        self::assertSame(
            ProspectiveMemberRemoval::RefundFailed,
            $this->service()->removeProspectiveMember($prospectiveMember),
        );
    }

    public function testDoesNotRefundSomeoneWhoNeverPaid(): void
    {
        $prospectiveMember = self::createStub(ProspectiveMember::class);
        $prospectiveMember->method('canBeDeleted')->willReturn(true);
        $prospectiveMember->method('hasPaid')->willReturn(false);

        $stripeService = $this->createMock(StripeService::class);
        $stripeService->expects(self::never())->method('createRefund');
        $this->stripeService = $stripeService;

        $memberService = $this->createMock(MemberService::class);
        $memberService->expects(self::once())->method('removeProspective');
        $this->memberService = $memberService;

        self::assertSame(
            ProspectiveMemberRemoval::Removed,
            $this->service()->removeProspectiveMember($prospectiveMember),
        );
    }

    /**
     * What "cannot be deleted" means is the prospective member's own business; this is only about respecting it.
     */
    public function testLeavesAProspectiveMemberThatSaysItCannotBeRemoved(): void
    {
        $prospectiveMember = self::createStub(ProspectiveMember::class);
        $prospectiveMember->method('canBeDeleted')->willReturn(false);

        $memberService = $this->createMock(MemberService::class);
        $memberService->expects(self::never())->method('removeProspective');
        $this->memberService = $memberService;

        self::assertSame(
            ProspectiveMemberRemoval::NotRemovable,
            $this->service()->removeProspectiveMember($prospectiveMember),
        );
    }

    /**
     * A Stripe service whose refund does not go through.
     *
     * Hand-written rather than a double: `createRefund()` is typed `?true`, and PHPUnit's generated double declares
     * that as plain `true`, so the failing answer cannot be configured on one.
     */
    private function stripeThatCannotRefund(): StripeService
    {
        return new class (
            new NullLogger(),
            self::createStub(ActionLinkRepository::class),
            self::createStub(CheckoutSessionRepository::class),
            self::createStub(MemberService::class),
        ) extends StripeService {
            public function __construct(
                LoggerInterface $logger,
                ActionLinkRepository $actionLinkRepository,
                CheckoutSessionRepository $checkoutSessionRepository,
                MemberService $memberService,
            ) {
                parent::__construct(
                    $logger,
                    $actionLinkRepository,
                    $checkoutSessionRepository,
                    $memberService,
                    '2024-06-20',
                    'sk_test',
                    'whsec_test',
                    'price_test',
                    'https://join.gewis.test/cancel',
                    'https://join.gewis.test/success',
                );
            }

            #[Override]
            public function hasRefund(ProspectiveMemberModel $prospectiveMember): ?bool
            {
                return false;
            }

            #[Override]
            public function createRefund(ProspectiveMemberModel $prospectiveMember): ?true
            {
                return null;
            }
        };
    }

    private function paymentLink(): PaymentLink
    {
        $paymentLink = new PaymentLink();
        $paymentLink->setProspectiveMember(new ProspectiveMember());

        return $paymentLink;
    }

    private function paidProspectiveMember(): ProspectiveMember
    {
        $prospectiveMember = self::createStub(ProspectiveMember::class);
        $prospectiveMember->method('canBeDeleted')->willReturn(true);
        $prospectiveMember->method('hasPaid')->willReturn(true);

        return $prospectiveMember;
    }

    private function service(string $now = '2026-08-20'): RegistrationService
    {
        return new RegistrationService(
            self::createStub(MailingListRepository::class),
            self::createStub(ProspectiveMemberRepository::class),
            $this->memberService ?? self::createStub(MemberService::class),
            $this->stripeService ?? self::createStub(StripeService::class),
            new MockClock($now),
        );
    }
}
