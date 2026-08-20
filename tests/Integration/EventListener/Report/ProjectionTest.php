<?php

declare(strict_types=1);

namespace App\Tests\Integration\EventListener\Report;

use App\Entity\Database\Enums\InstallationFunctions;
use App\Entity\Report\Member as ReportMember;
use App\Entity\Report\Meeting as ReportMeeting;
use App\Entity\Report\Organ as ReportOrgan;
use App\Entity\Report\OrganMember as ReportOrganMember;
use App\EventListener\Report\DatabaseDeletionListener;
use App\EventListener\Report\DatabaseUpdateListener;
use App\Tests\Support\LedgerBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * ReportDB is a projection of the ledger and a contract with GEWISWEB, which reads its tables directly. What the
 * listeners write as the ledger is written is therefore not an internal detail: it is another application's input.
 */
#[CoversClass(DatabaseUpdateListener::class)]
#[CoversClass(DatabaseDeletionListener::class)]
class ProjectionTest extends KernelTestCase
{
    private EntityManagerInterface $ledger;
    private EntityManagerInterface $report;
    private LedgerBuilder $build;

    #[Override]
    protected function setUp(): void
    {
        self::bootKernel();

        $ledger = self::getContainer()->get(EntityManagerInterface::class);
        $report = self::getContainer()->get('doctrine')->getManager('report');
        self::assertInstanceOf(EntityManagerInterface::class, $report);

        $this->ledger = $ledger;
        $this->report = $report;
        $this->build = new LedgerBuilder($ledger);
    }

    public function testWritingAMemberWritesTheProjectedMember(): void
    {
        $member = $this->build->member();

        $projected = $this->report->getRepository(ReportMember::class)->find($member->getLidnr());

        self::assertNotNull($projected);
        self::assertSame($member->getLastName(), $projected->getLastName());
        self::assertSame($member->getEmail(), $projected->getEmail());
    }

    public function testWritingAMeetingWritesTheProjectedMeeting(): void
    {
        $meeting = $this->build->meeting();

        $projected = $this->report->getRepository(ReportMeeting::class)->find([
            'type' => $meeting->getType(),
            'number' => $meeting->getNumber(),
        ]);

        self::assertNotNull($projected);
        self::assertEquals($meeting->getDate(), $projected->getDate());
    }

    /**
     * A foundation is not projected as a subdecision alone: the organ it founds is derived from it, and that is the
     * table GEWISWEB reads to know which bodies exist.
     */
    public function testFoundingAnOrganDerivesTheOrgan(): void
    {
        $foundation = $this->build->foundOrgan($this->build->meeting(), 'TTC', 'Testtaartcommissie');

        $organ = $this->organOf($foundation->getAbbr());

        self::assertNotNull($organ);
        self::assertSame('Testtaartcommissie', $organ->getName());
        self::assertNull($organ->getAbrogationDate());
    }

    public function testAbrogatingAnOrganDatesItRatherThanRemovingIt(): void
    {
        $meeting = $this->build->meeting();
        $foundation = $this->build->foundOrgan($meeting, 'ATC');
        $this->build->abrogate($this->build->meeting(date: '2027-08-20'), $foundation);

        $organ = $this->organOf('ATC');

        self::assertNotNull($organ);
        self::assertSame('2027-08-20', $organ->getAbrogationDate()?->format('Y-m-d'));
    }

    /**
     * The membership of a body, which is what the API hands out and what GEWISWEB shows on a member's page.
     */
    public function testInstallingSomeoneDerivesTheirOrganMembership(): void
    {
        $meeting = $this->build->meeting();
        $foundation = $this->build->foundOrgan($meeting, 'ITC');
        $member = $this->build->member();
        $this->build->install($meeting, $foundation, $member, InstallationFunctions::Member);

        $organMember = $this->organMemberOf('ITC');

        self::assertNotNull($organMember);
        self::assertSame($member->getLidnr(), $organMember->getMember()->getLidnr());
        self::assertSame(InstallationFunctions::Member, $organMember->getFunction());
        self::assertNull($organMember->getDischargeDate());
    }

    public function testDischargingSomeoneEndsTheOrganMembershipInPlace(): void
    {
        $meeting = $this->build->meeting();
        $foundation = $this->build->foundOrgan($meeting, 'DTC');
        $installation = $this->build->install(
            $meeting,
            $foundation,
            $this->build->member(),
            InstallationFunctions::Member,
        );
        $this->build->discharge($this->build->meeting(date: '2027-02-01'), $installation);

        $organMember = $this->organMemberOf('DTC');

        self::assertNotNull($organMember);
        self::assertSame('2027-02-01', $organMember->getDischargeDate()?->format('Y-m-d'));
    }

    /**
     * The other direction: what leaves the ledger has to leave the projection, or GEWISWEB goes on showing it.
     */
    public function testRemovingADecisionRemovesWhatWasDerivedFromIt(): void
    {
        $meeting = $this->build->meeting();
        $foundation = $this->build->foundOrgan($meeting, 'RTC');

        self::assertNotNull($this->organOf('RTC'));

        $this->ledger->remove($foundation->getDecision());
        $this->ledger->flush();

        self::assertNull($this->organOf('RTC'));
    }

    private function organOf(string $abbreviation): ?ReportOrgan
    {
        return $this->report->getRepository(ReportOrgan::class)->findOneBy(['abbr' => $abbreviation]);
    }

    private function organMemberOf(string $abbreviation): ?ReportOrganMember
    {
        $organ = $this->organOf($abbreviation);

        self::assertNotNull($organ);

        return $this->report->getRepository(ReportOrganMember::class)->findOneBy(['organ' => $organ]);
    }
}
