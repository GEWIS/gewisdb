<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Database;

use App\Entity\Database\Enums\InstallationFunctions;
use App\Entity\Database\Enums\MembershipTypes;
use App\Entity\Database\Enums\Studies;
use App\Entity\Database\Member as MemberModel;
use App\Service\Database\Member;
use DateTime;
use App\Tests\Support\LedgerBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormInterface;

/**
 * What happens to a member over time: the membership being extended, and the member being taken out of the register.
 */
#[CoversClass(Member::class)]
class MemberTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private Member $memberService;
    private LedgerBuilder $build;

    #[Override]
    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->memberService = self::getContainer()->get(Member::class);
        $this->build = new LedgerBuilder($this->entityManager);
    }

    /**
     * A membership is extended to the end of the association year that follows the one it runs out in, never by a
     * year from today.
     */
    public function testExtendingRunsToTheEndOfTheNextAssociationYear(): void
    {
        $member = $this->build->member(
            MembershipTypes::Ordinary,
            '2025-08-01',
            '2026-07-01',
        );

        self::assertSame(
            '2027-07-01',
            $this->memberService->getExtendedExpiration($member)->format('Y-m-d'),
        );
    }

    /**
     * The new period starts where the old one ended, so the two together are one unbroken membership.
     */
    public function testExtendingAddsAPeriodStartingWhereTheLastOneEnded(): void
    {
        $member = $this->build->member(
            MembershipTypes::Ordinary,
            '2025-08-01',
            '2026-07-01',
        );

        $extended = $this->memberService->expiration($member, $this->submittedForm());
        $this->entityManager->flush();

        $added = $member->getLastMembership();

        self::assertNotNull($extended);
        self::assertCount(2, $member->getMemberships());
        self::assertNotNull($added);
        self::assertSame('2026-07-01', $added->getStartDate()->format('Y-m-d'));
        self::assertSame('2027-07-01', $added->getEndDate()->format('Y-m-d'));
    }

    public function testAnExtensionIsNotCarriedOutOnAnInvalidForm(): void
    {
        $member = $this->build->member();

        self::assertNull($this->memberService->expiration($member, $this->submittedForm(valid: false)));
        self::assertCount(1, $member->getMemberships());
    }

    /**
     * Nothing points at a plain member, so there is nothing to keep and they go.
     */
    public function testRemovesAMemberThatNothingRefersTo(): void
    {
        $member = $this->build->member();
        $lidnr = $member->getLidnr();

        self::assertTrue($this->memberService->canRemove($member));

        $this->memberService->remove($member);
        $this->entityManager->flush();
        $this->entityManager->clear();

        self::assertNull($this->entityManager->getRepository(MemberModel::class)->find($lidnr));
    }

    /**
     * Someone who appears in the decisions cannot be deleted without taking a hole out of the historical record, so
     * they are stripped of everything personal and kept.
     */
    public function testStripsRatherThanRemovesAMemberTheDecisionsReferTo(): void
    {
        $meeting = $this->build->meeting();
        $member = $this->build->member();
        $this->build->install(
            $meeting,
            $this->build->foundOrgan($meeting, 'KTC'),
            $member,
            InstallationFunctions::Member,
        );
        $lidnr = $member->getLidnr();

        self::assertFalse($this->memberService->canRemove($member));

        $this->memberService->remove($member);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $kept = $this->entityManager->getRepository(MemberModel::class)->find($lidnr);

        self::assertNotNull($kept);
        self::assertTrue($kept->getDeleted());
        self::assertTrue($kept->getHidden());
        self::assertNull($kept->getEmail());
        self::assertNull($kept->getStudentNumber());
        self::assertSame(Studies::Unknown, $kept->getStudy());
        self::assertSame('0001-01-01', $kept->getBirth()->format('Y-m-d'));
    }

    /**
     * The unattended sweep: gone if every membership ended on or before the date, kept otherwise.
     */
    public function testSweepsOutOnlyTheMembersWhoseMembershipHadRunOut(): void
    {
        $expired = $this->build->member(
            MembershipTypes::Ordinary,
            '2019-08-01',
            '2020-07-01',
        );
        $current = $this->build->member();
        $expiredLidnr = $expired->getLidnr();
        $currentLidnr = $current->getLidnr();

        $this->memberService->removeExpiredMembers(new DateTime('2020-07-01'));
        $this->entityManager->flush();
        $this->entityManager->clear();

        $repository = $this->entityManager->getRepository(MemberModel::class);

        self::assertNull($repository->find($expiredLidnr));
        self::assertNotNull($repository->find($currentLidnr));
    }

    /**
     * @return FormInterface<mixed>
     */
    private function submittedForm(bool $valid = true): FormInterface
    {
        $form = self::createStub(FormInterface::class);
        $form->method('isValid')->willReturn($valid);

        return $form;
    }
}
