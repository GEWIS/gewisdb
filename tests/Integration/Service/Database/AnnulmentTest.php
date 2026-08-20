<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Database;

use App\Entity\Database\Enums\InstallationFunctions;
use App\Entity\Database\SubDecision\Installation;
use App\Exception\Database\AnnulmentNotPossible;
use App\Service\Database\Annulment;
use App\Tests\Support\LedgerBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The ledger's central rule, against a real database: a decision may be annulled only while it is still the last word
 * on what it decided about. Every entity family has its own idea of what "built on" means, which is why each is
 * checked here rather than one standing in for the rest.
 *
 * Every write is rolled back by dama/doctrine-test-bundle, so the seed these decisions are added to survives the run.
 */
#[CoversClass(Annulment::class)]
class AnnulmentTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private Annulment $annulment;
    private LedgerBuilder $build;

    #[Override]
    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->annulment = self::getContainer()->get(Annulment::class);
        $this->build = new LedgerBuilder($this->entityManager);
    }

    public function testAllowsAnnullingADecisionNothingBuildsOn(): void
    {
        $foundation = $this->build->foundOrgan($this->build->meeting());

        self::assertSame(
            [],
            $this->annulment->assertDecisionCanBeAnnulled($foundation->getDecision()),
        );
    }

    /**
     * The organ would go on existing through the people installed in it, which is not a state the register can hold.
     */
    public function testRefusesToAnnulTheFoundationOfAnOrganPeopleWereInstalledIn(): void
    {
        $meeting = $this->build->meeting();
        $foundation = $this->build->foundOrgan($meeting);
        $this->build->install(
            $this->build->meeting(date: '2026-10-01'),
            $foundation,
            $this->build->member(),
            InstallationFunctions::Member,
        );

        $this->expectException(AnnulmentNotPossible::class);

        $this->annulment->assertDecisionCanBeAnnulled($foundation->getDecision());
    }

    public function testRefusesToAnnulAnInstallationThatWasDischargedAgain(): void
    {
        $installation = $this->anInstallation();
        $this->build->discharge($this->build->meeting(date: '2027-02-01'), $installation);

        $this->expectException(AnnulmentNotPossible::class);

        $this->annulment->assertDecisionCanBeAnnulled($installation->getDecision());
    }

    /**
     * A reappointment is a decision about the installation just as much as a discharge is.
     */
    public function testRefusesToAnnulAnInstallationThatWasProlonged(): void
    {
        $installation = $this->anInstallation();
        $this->build->reappoint($this->build->meeting(date: '2027-02-01'), $installation);

        $this->expectException(AnnulmentNotPossible::class);

        $this->annulment->assertDecisionCanBeAnnulled($installation->getDecision());
    }

    /**
     * Putting the organ back would leave members installed in a body that, at that point, no longer existed.
     */
    public function testRefusesToAnnulAnAbrogationThatPeopleWereInstalledAfter(): void
    {
        $meeting = $this->build->meeting();
        $foundation = $this->build->foundOrgan($meeting);
        $abrogation = $this->build->abrogate($this->build->meeting(date: '2027-02-01'), $foundation);
        $this->build->install(
            $this->build->meeting(date: '2027-06-01'),
            $foundation,
            $this->build->member(),
            InstallationFunctions::Member,
        );

        $this->expectException(AnnulmentNotPossible::class);

        $this->annulment->assertDecisionCanBeAnnulled($abrogation->getDecision());
    }

    public function testRefusesToAnnulABoardInstallationThatWasReleased(): void
    {
        $installation = $this->build->installBoard($this->build->meeting(), $this->build->member());
        $this->build->releaseBoard($this->build->meeting(date: '2027-09-01'), $installation);

        $this->expectException(AnnulmentNotPossible::class);

        $this->annulment->assertDecisionCanBeAnnulled($installation->getDecision());
    }

    public function testRefusesToAnnulAKeyGrantingThatWasWithdrawn(): void
    {
        $granting = $this->build->grantKey($this->build->meeting(), $this->build->member());
        $this->build->withdrawKey($this->build->meeting(date: '2026-10-01'), $granting);

        $this->expectException(AnnulmentNotPossible::class);

        $this->annulment->assertDecisionCanBeAnnulled($granting->getDecision());
    }

    /**
     * The withdrawal is the last word on that key code, so it is the one that can still be taken back.
     */
    public function testAllowsAnnullingTheWithdrawalOfAKeyCode(): void
    {
        $granting = $this->build->grantKey($this->build->meeting(), $this->build->member());
        $withdrawal = $this->build->withdrawKey($this->build->meeting(date: '2026-10-01'), $granting);

        self::assertSame(
            [],
            $this->annulment->assertDecisionCanBeAnnulled($withdrawal->getDecision()),
        );
    }

    public function testAllowsAnnullingTheLastWordOnAnOrgan(): void
    {
        $installation = $this->anInstallation();
        $discharge = $this->build->discharge($this->build->meeting(date: '2027-02-01'), $installation);

        self::assertSame(
            [],
            $this->annulment->assertDecisionCanBeAnnulled($discharge->getDecision()),
        );
    }

    /**
     * Annulling a reappointment shortens a term without saying when it ended, which GEWISDB cannot work out on its
     * own. That is worth pointing out to whoever is entering it, and not worth refusing.
     */
    public function testPointsOutRatherThanRefusesWhatFollowedAReappointment(): void
    {
        $installation = $this->anInstallation();
        $reappointment = $this->build->reappoint($this->build->meeting(date: '2027-02-01'), $installation);
        $this->build->discharge($this->build->meeting(date: '2027-06-01'), $installation);

        $warnings = $this->annulment->assertDecisionCanBeAnnulled($reappointment->getDecision());

        self::assertCount(1, $warnings);
        self::assertStringContainsString('discharged after being reappointed', $warnings[0]);
    }

    /**
     * Taking an annulment back restores exactly what it took away, so it holds to the same rule.
     */
    public function testAllowsDeletingAnAnnulmentNothingHappenedAfter(): void
    {
        $foundation = $this->build->foundOrgan($this->build->meeting());
        $annulment = $this->build->annul(
            $this->build->meeting(date: '2027-02-01'),
            $foundation->getDecision(),
        );

        $this->annulment->assertAnnulmentCanBeDeleted($annulment);

        $this->expectNotToPerformAssertions();
    }

    /**
     * Whatever was decided afterwards was decided in a world where the annulled decision did not exist; putting it
     * back would silently invalidate that. Here the annulment says the member was never discharged, a later meeting
     * discharges them for real, and restoring the first discharge would leave the installation ended twice.
     */
    public function testRefusesToDeleteAnAnnulmentTheInstallationWasDecidedAboutAfter(): void
    {
        $installation = $this->anInstallation();
        $discharge = $this->build->discharge($this->build->meeting(date: '2027-02-01'), $installation);
        $annulment = $this->build->annul(
            $this->build->meeting(date: '2027-06-01'),
            $discharge->getDecision(),
        );
        $this->build->discharge($this->build->meeting(date: '2027-10-01'), $installation);

        $this->expectException(AnnulmentNotPossible::class);

        $this->annulment->assertAnnulmentCanBeDeleted($annulment);
    }

    private function anInstallation(): Installation
    {
        $meeting = $this->build->meeting();

        return $this->build->install(
            $meeting,
            $this->build->foundOrgan($meeting),
            $this->build->member(),
            InstallationFunctions::Member,
        );
    }
}
