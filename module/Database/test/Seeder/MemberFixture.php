<?php

declare(strict_types=1);

namespace DatabaseTest\Seeder;

use Application\Model\Enums\MembershipTypes;
use Application\Model\Enums\PostalRegions;
use Database\Model\Address;
use Database\Model\CheckoutSession;
use Database\Model\Enums\CheckoutSessionStates;
use Database\Model\Enums\Studies;
use Database\Model\Member as MemberModel;
use Database\Model\Membership as MembershipModel;
use Database\Model\ProspectiveMember;
use DateTime;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Override;

use function count;
use function min;
use function sprintf;

class MemberFixture extends AbstractFixture
{
    public const string REF_MEMBER_STUDENT = 'student';
    public const string REF_MEMBER_EXTERNAL = 'external';
    public const string REF_MEMBER_GRADUATE = 'graduate';
    public const string REF_MEMBER_PROSPECTIVE = 'prospective';

    /**
     * Members below are crafted to surface on the "members requiring attention" overview. The ones that need to count
     * as active organ members are referenced here so {@see DecisionFixture} can install them into an organ.
     */
    public const string REF_MEMBER_ATTN_ORDINARY_ACTIVE = 'attn-ordinary-active';
    public const string REF_MEMBER_ATTN_EXTERNAL_ACTIVE = 'attn-external-active';
    public const string REF_MEMBER_ATTN_GRADUATE_ACTIVE = 'attn-graduate-active';
    public const string REF_MEMBER_ATTN_MISCLASSIFIED = 'attn-misclassified';

    #[Override]
    public function load(ObjectManager $manager): void
    {
        $lastExpiryDate = new DateTime();
        $lastExpiryDate->setDate((int) $lastExpiryDate->format('Y') + 1, 7, 1);

        /** Prospective member */
        $pros = new ProspectiveMember();
        $pros->setInitials('T.A.');
        $pros->setFirstName('Tara');
        $pros->setMiddleName('');
        $pros->setLastName('Testdata');
        $pros->setTueUsername('20190001');
        $pros->setBirth(new DateTime('2001-01-01'));
        $pros->setEmail('tara@example.com');
        $pros->setPaid(20);
        $pros->setChangedOn(new DateTime());
        $prosAddress = new Address();
        $prosAddress->setStreet('Teststraat');
        $prosAddress->setNumber('123');
        $prosAddress->setPostalCode('5600 AA');
        $prosAddress->setCity('Eindhoven');
        $prosAddress->setPhone('1');
        $prosAddress->setCountry(PostalRegions::Netherlands);
        $pros->setAddress($prosAddress);
        $pros->setStudy(Studies::BAM);

        $manager->persist($pros);
        $this->addReference(self::REF_MEMBER_PROSPECTIVE, $pros);

        $checkoutSession = new CheckoutSession();
        $checkoutSession->setCheckoutId('123');
        $checkoutSession->setProspectiveMember($pros);
        $checkoutSession->setCreated(new DateTime());
        $checkoutSession->setExpiration(new DateTime());
        $checkoutSession->setState(CheckoutSessionStates::Paid);
        $manager->persist($checkoutSession);

        /** Student */
        $student = new MemberModel();
        $student->setInitials('T.');
        $student->setFirstName('Timon');
        $student->setMiddleName('de');
        $student->setLastName('Teststudent');
        $student->setEmail('timon@example.com');
        $student->setBirth(new DateTime('2000-01-01'));
        $student->setChangedOn(new DateTime());
        $student->setTueUsername('20180001');
        $student->setStudy(Studies::BAM);

        $startDate = new DateTime('2018-08-14 midnight');
        while ($startDate < new DateTime()) {
            $membership = new MembershipModel(
                member: $student,
                type: MembershipTypes::Ordinary,
                startDate: clone $startDate,
                endDate: null,
            );
            $student->addMembership($membership);

            $startDate = $membership->getEndDate();
        }

        $manager->persist($student);
        $this->addReference(self::REF_MEMBER_STUDENT, $student);

        /** External */
        $external = new MemberModel();
        $external->setInitials('J.');
        $external->setFirstName('Joe');
        $external->setMiddleName('');
        $external->setLastName('Bloggs');
        $external->setEmail('joe@example.com');
        $external->setBirth(new DateTime('1999-01-01'));
        $external->setChangedOn(new DateTime());
        $external->setStudy(Studies::Other);

        $startDate = new DateTime('2017-08-15 midnight');
        $externalDate = new DateTime('2020-06-30 midnight');
        while ($startDate < new DateTime()) {
            $membership = new MembershipModel(
                member: $external,
                type: $startDate < $externalDate ? MembershipTypes::Ordinary : MembershipTypes::External,
                startDate: clone $startDate,
                endDate: null,
            );
            $external->addMembership($membership);

            $startDate = $membership->getEndDate();
        }

        $manager->persist($external);
        $this->addReference(self::REF_MEMBER_EXTERNAL, $external);

        /** Graduate */
        $graduate = new MemberModel();
        $graduate->setInitials('J.H.');
        $graduate->setFirstName('Jack');
        $graduate->setMiddleName('van');
        $graduate->setLastName('Lint');
        $graduate->setEmail('vanlint@example.com');
        $graduate->setBirth(new DateTime('1932-09-01'));
        $graduate->setChangedOn(new DateTime('1990-07-01'));
        $graduate->setStudy(Studies::None);

        $startDate = new DateTime('1989-08-15 midnight');
        $graduateDate = new DateTime('1994-06-30 midnight');
        while ($startDate < new DateTime()) {
            $membership = new MembershipModel(
                member: $graduate,
                type: $startDate < $graduateDate ? MembershipTypes::Ordinary : MembershipTypes::Graduate,
                startDate: clone $startDate,
                endDate: null,
            );
            $graduate->addMembership($membership);

            $startDate = $membership->getEndDate();
        }

        $manager->persist($graduate);
        $this->addReference(self::REF_MEMBER_GRADUATE, $graduate);

        /**
         * Members requiring attention.
         *
         * All dates are derived from the seed time: histories are full association years following valid transitions
         * from the transition matrix. "Expiring soon" members end on the upcoming July 1; the already-expired/boundary
         * and out-of-window control members end on a deliberate day offset from seed time so they can be used for the
         * day-based finder thresholds.
         */
        $now = new DateTime();
        $year = (int) $now->format('Y');
        $nextJul1 = new DateTime($year . '-07-01 midnight');
        if ($nextJul1 <= $now) {
            $nextJul1->modify('+1 year');
        }

        // A1 (#1): hidden + missing email; must disappear once the finders also filter hidden.
        $this->makeAttentionMember(
            manager: $manager,
            initials: 'H.',
            firstName: 'Henk',
            middleName: '',
            lastName: 'HiddenNoEmail',
            birth: new DateTime()->modify('-21 years'),
            email: null,
            tueUsername: $this->studentNumber(4, 2),
            study: Studies::BAM,
            segments: $this->associationYearChain(4, $nextJul1),
            hidden: true,
        );

        // A2 (#5): same-day membership (start == end) is silently dropped despite missing email.
        $sameDay = new DateTime()->modify('-10 days');
        $this->makeAttentionMember(
            manager: $manager,
            initials: 'S.',
            firstName: 'Sanne',
            middleName: '',
            lastName: 'SamedayDropped',
            birth: new DateTime()->modify('-21 years'),
            email: null,
            tueUsername: $this->studentNumber(1, 3),
            study: Studies::BAM,
            segments: [
                [MembershipTypes::Ordinary, $sameDay, clone $sameDay],
            ],
        );

        // A3: visible member missing email -> control; stays after the #1 fix.
        $this->makeAttentionMember(
            manager: $manager,
            initials: 'V.',
            firstName: 'Vera',
            middleName: '',
            lastName: 'VisibleNoEmail',
            birth: new DateTime()->modify('-22 years'),
            email: null,
            tueUsername: $this->studentNumber(3, 4),
            study: Studies::BAM,
            segments: $this->associationYearChain(3, $nextJul1),
        );

        // A4: ordinary member without student ID -> MissingStudentIdOrdinary.
        $this->makeAttentionMember(
            manager: $manager,
            initials: 'N.',
            firstName: 'Nora',
            middleName: '',
            lastName: 'NoStudentId',
            birth: new DateTime()->modify('-22 years'),
            email: 'nostudentid@example.com',
            tueUsername: null,
            study: Studies::BAM,
            segments: $this->associationYearChain(3, $nextJul1),
        );

        // B1: ordinary, active organ member, expiring -> ExpiringOrdinaryActive (has Renew button).
        $ordinaryActive = $this->makeAttentionMember(
            manager: $manager,
            initials: 'O.',
            firstName: 'Olaf',
            middleName: '',
            lastName: 'OrdinaryActive',
            birth: new DateTime()->modify('-22 years'),
            email: 'ordinaryactive@example.com',
            tueUsername: $this->studentNumber(4, 5),
            study: Studies::BAM,
            segments: $this->associationYearChain(4, $nextJul1),
        );
        $this->addReference(self::REF_MEMBER_ATTN_ORDINARY_ACTIVE, $ordinaryActive);

        // B2: ordinary, non-active, expiring -> ExpiringOrdinaryNonActive.
        $this->makeAttentionMember(
            manager: $manager,
            initials: 'O.',
            firstName: 'Otis',
            middleName: '',
            lastName: 'OrdinaryNonActive',
            birth: new DateTime()->modify('-23 years'),
            email: 'ordinarynonactive@example.com',
            tueUsername: $this->studentNumber(4, 6),
            study: Studies::BAM,
            segments: $this->associationYearChain(4, $nextJul1),
        );

        // B3: external, active, expiring -> ExpiringExternalActive. Ordinary -> External applies here because they
        // were an active organ member whom the board kept on as external after their studies ended.
        $externalActive = $this->makeAttentionMember(
            manager: $manager,
            initials: 'E.',
            firstName: 'Ellen',
            middleName: '',
            lastName: 'ExternalActive',
            birth: new DateTime()->modify('-24 years'),
            email: 'externalactive@example.com',
            tueUsername: $this->studentNumber(6, 7),
            study: Studies::Other,
            segments: $this->associationYearChain(6, $nextJul1, MembershipTypes::External, 3),
        );
        $this->addReference(self::REF_MEMBER_ATTN_EXTERNAL_ACTIVE, $externalActive);

        // B4: external, non-active, expiring -> ExpiringExternalNonActive. Joined as an external (e.g. a PhD or
        // non-MCS student), so external from the start rather than via an Ordinary -> External conversion.
        $this->makeAttentionMember(
            manager: $manager,
            initials: 'E.',
            firstName: 'Evert',
            middleName: '',
            lastName: 'ExternalNonActive',
            birth: new DateTime()->modify('-26 years'),
            email: 'externalnonactive@example.com',
            tueUsername: $this->studentNumber(4, 8),
            study: Studies::Other,
            segments: $this->associationYearChain(4, $nextJul1, MembershipTypes::External, 4),
        );

        // B5 (#7): graduate installed as inactive organ member -> ExpiringGraduateActiveInactive (no button).
        $graduateActive = $this->makeAttentionMember(
            manager: $manager,
            initials: 'G.',
            firstName: 'Gerda',
            middleName: '',
            lastName: 'GraduateActive',
            birth: new DateTime()->modify('-31 years'),
            email: 'graduateactive@example.com',
            tueUsername: $this->studentNumber(7, 9),
            study: Studies::None,
            segments: $this->associationYearChain(7, $nextJul1, MembershipTypes::Graduate, 2),
        );
        $this->addReference(self::REF_MEMBER_ATTN_GRADUATE_ACTIVE, $graduateActive);

        // B6 (#8): graduate, non-active, expiring -> never surfaced; the graduate finder only includes active.
        // Reached graduate status via the common External -> Graduate path (an external who stopped studying).
        $this->makeAttentionMember(
            manager: $manager,
            initials: 'G.',
            firstName: 'Gijs',
            middleName: '',
            lastName: 'GraduateNonActive',
            birth: new DateTime()->modify('-32 years'),
            email: 'graduatenonactive@example.com',
            tueUsername: $this->studentNumber(6, 10),
            study: Studies::None,
            segments: $this->associationYearPhases(
                [
                    [MembershipTypes::External, 4],
                    [MembershipTypes::Graduate, 2],
                ],
                $nextJul1,
            ),
        );

        // C1 (#3): ordinary, non-active, already expired 30d ago -> labeled "expiring soon" with a past date.
        $this->makeAttentionMember(
            manager: $manager,
            initials: 'P.',
            firstName: 'Peter',
            middleName: '',
            lastName: 'OrdinaryExpired30',
            birth: new DateTime()->modify('-23 years'),
            email: 'ordinaryexpired30@example.com',
            tueUsername: $this->studentNumber(4, 11),
            study: Studies::BAM,
            segments: $this->associationYearChain(4, new DateTime()->modify('-30 days')),
        );

        // C2 (#6): ordinary, non-active, ended exactly 90d ago at midnight -> off-by-time boundary.
        $this->makeAttentionMember(
            manager: $manager,
            initials: 'B.',
            firstName: 'Bram',
            middleName: '',
            lastName: 'OrdinaryBoundary90',
            birth: new DateTime()->modify('-23 years'),
            email: 'ordinaryboundary90@example.com',
            tueUsername: $this->studentNumber(4, 12),
            study: Studies::BAM,
            segments: $this->associationYearChain(4, new DateTime()->modify('-90 days')),
        );

        // C3: ordinary, expired 180d ago -> control; beyond the 90-day window, must NOT appear.
        $this->makeAttentionMember(
            manager: $manager,
            initials: 'L.',
            firstName: 'Lotte',
            middleName: '',
            lastName: 'OrdinaryExpired180',
            birth: new DateTime()->modify('-24 years'),
            email: 'ordinaryexpired180@example.com',
            tueUsername: $this->studentNumber(4, 13),
            study: Studies::BAM,
            segments: $this->associationYearChain(4, new DateTime()->modify('-180 days')),
        );

        // C4: ordinary, expiring 180d out -> control; beyond the +30-day window, must NOT appear.
        $this->makeAttentionMember(
            manager: $manager,
            initials: 'F.',
            firstName: 'Freek',
            middleName: '',
            lastName: 'OrdinaryFuture180',
            birth: new DateTime()->modify('-22 years'),
            email: 'ordinaryfuture180@example.com',
            tueUsername: $this->studentNumber(4, 14),
            study: Studies::BAM,
            segments: $this->associationYearChain(4, new DateTime()->modify('+180 days')),
        );

        // C5: external, non-active, already expired 30d ago -> ExpiringExternalNonActive (external past coverage).
        // Joined as an external (e.g. a PhD/EngD or non-MCS student), so external from the start.
        $this->makeAttentionMember(
            manager: $manager,
            initials: 'X.',
            firstName: 'Xander',
            middleName: '',
            lastName: 'ExternalExpired30',
            birth: new DateTime()->modify('-26 years'),
            email: 'externalexpired30@example.com',
            tueUsername: $this->studentNumber(4, 15),
            study: Studies::Other,
            segments: $this->associationYearChain(
                4,
                new DateTime()->modify('-30 days'),
                MembershipTypes::External,
                4,
            ),
        );

        // D1 (#4): ordinary, expired 30d ago, active at expiry but discharged 10d ago -> misclassified non-active.
        $misclassified = $this->makeAttentionMember(
            manager: $manager,
            initials: 'M.',
            firstName: 'Marit',
            middleName: '',
            lastName: 'Misclassified',
            birth: new DateTime()->modify('-26 years'),
            email: 'misclassified@example.com',
            tueUsername: $this->studentNumber(4, 16),
            study: Studies::BAM,
            segments: $this->associationYearChain(4, new DateTime()->modify('-30 days')),
        );
        $this->addReference(self::REF_MEMBER_ATTN_MISCLASSIFIED, $misclassified);

        // E1: master who joined in the second semester (February) rather than the August introduction; their first
        // membership runs February -> next July 1, then full association years (ExpiringOrdinaryNonActive).
        $this->makeAttentionMember(
            manager: $manager,
            initials: 'I.',
            firstName: 'Iris',
            middleName: '',
            lastName: 'SpringMaster',
            birth: new DateTime()->modify('-24 years'),
            email: 'springmaster@example.com',
            tueUsername: $this->studentNumber(2, 17),
            study: Studies::MCSE,
            segments: $this->associationYearChain(2, $nextJul1, joinMonth: 2, joinDay: 10),
        );

        // E2: external (PhD) who joined mid-year in the autumn (November) -> ExpiringExternalNonActive.
        $this->makeAttentionMember(
            manager: $manager,
            initials: 'D.',
            firstName: 'Daan',
            middleName: '',
            lastName: 'AutumnExternal',
            birth: new DateTime()->modify('-27 years'),
            email: 'autumnexternal@example.com',
            tueUsername: $this->studentNumber(3, 18),
            study: Studies::PhDCS,
            segments: $this->associationYearChain(
                3,
                $nextJul1,
                MembershipTypes::External,
                3,
                joinMonth: 11,
                joinDay: 1,
            ),
        );

        $manager->flush();
    }

    /**
     * Create, populate and persist a member with a membership history. Returns it can be used as a reference for
     * cross-fixture wiring (e.g., organ installations in DecisionFixture).
     *
     * @param list<array{0: MembershipTypes, 1: DateTime, 2: DateTime}> $segments [type, start, end]
     */
    private function makeAttentionMember(
        ObjectManager $manager,
        string $initials,
        string $firstName,
        string $middleName,
        string $lastName,
        DateTime $birth,
        ?string $email,
        ?string $tueUsername,
        Studies $study,
        array $segments,
        bool $hidden = false,
    ): MemberModel {
        $member = new MemberModel();
        $member->setInitials($initials);
        $member->setFirstName($firstName);
        $member->setMiddleName($middleName);
        $member->setLastName($lastName);
        $member->setBirth($birth);
        $member->setChangedOn(new DateTime());
        $member->setStudy($study);

        if (null !== $email) {
            $member->setEmail($email);
        }

        if (null !== $tueUsername) {
            $member->setTueUsername($tueUsername);
        }

        if ($hidden) {
            $member->setHidden(true);
        }

        foreach ($segments as [$type, $startDate, $endDate]) {
            $member->addMembership(new MembershipModel(
                member: $member,
                type: $type,
                startDate: $startDate,
                endDate: $endDate,
            ));
        }

        $manager->persist($member);

        return $member;
    }

    /**
     * Build a chain of association-year memberships for a member who has been around $years association years. The last
     * $finalTypeYears memberships use $finalType (earlier years are Ordinary), modelling a single transition like the
     * organ-based Ordinary -> External or the common Ordinary -> Graduate. Pass $finalTypeYears equal to $years for a
     * member who joined in that type straight away (e.g. a PhD who joined as External).
     *
     * @return list<array{0: MembershipTypes, 1: DateTime, 2: DateTime}>
     */
    private function associationYearChain(
        int $years,
        DateTime $finalEnd,
        MembershipTypes $finalType = MembershipTypes::Ordinary,
        int $finalTypeYears = 1,
        int $joinMonth = 8,
        int $joinDay = 20,
    ): array {
        $finalTypeYears = min($finalTypeYears, $years);
        $ordinaryYears = $years - $finalTypeYears;

        $phases = [];
        if ($ordinaryYears > 0) {
            $phases[] = [MembershipTypes::Ordinary, $ordinaryYears];
        }

        $phases[] = [$finalType, $finalTypeYears];

        return $this->associationYearPhases($phases, $finalEnd, $joinMonth, $joinDay);
    }

    /**
     * Build a chain of association-year memberships from a list of [type, number-of-years] phases, allowing multi-step
     * histories. Memberships run July 1 -> July 1; the first one starts on the join date (the introduction week by
     * default, but it can be mid-year for someone who joins later). The final membership ends on $finalEnd.
     *
     * @param list<array{0: MembershipTypes, 1: int}> $phases ordered [type, years] phases
     *
     * @return list<array{0: MembershipTypes, 1: DateTime, 2: DateTime}>
     */
    private function associationYearPhases(
        array $phases,
        DateTime $finalEnd,
        int $joinMonth = 8,
        int $joinDay = 20,
    ): array {
        $types = [];
        foreach ($phases as [$type, $years]) {
            for ($i = 0; $i < $years; $i++) {
                $types[] = $type;
            }
        }

        $count = count($types);
        $finalStartYear = (int) $this->julyFirstBefore($finalEnd)->format('Y');
        $joinYear = $finalStartYear - ($count - 1);
        // August (and later) joins fall in the first calendar year of the association year; earlier months fall in the
        // next calendar year.
        $firstYear = $joinMonth >= 7 ? $joinYear : $joinYear + 1;

        $segments = [];
        for ($i = 0; $i < $count; $i++) {
            $start = 0 === $i
                ? new DateTime(sprintf('%d-%02d-%02d 00:00:00', $firstYear, $joinMonth, $joinDay))
                : new DateTime(($joinYear + $i) . '-07-01 midnight');
            $end = $i === $count - 1
                ? clone $finalEnd
                : new DateTime(($joinYear + $i + 1) . '-07-01 midnight');

            $segments[] = [$types[$i], $start, $end];
        }

        return $segments;
    }

    /**
     * Return the latest July 1 (association-year boundary) strictly before the given date.
     */
    private function julyFirstBefore(DateTime $date): DateTime
    {
        $julyFirst = new DateTime($date->format('Y') . '-07-01 midnight');
        if ($julyFirst >= $date) {
            $julyFirst->modify('-1 year');
        }

        return $julyFirst;
    }

    /**
     * Build a TU/e style student number (YYYYABCD) for a member who enrolled $yearsAgo years ago. $sequence is the
     * trailing serial; it starts at 2 below because the student and prospective fixtures above already use serial 1.
     */
    private function studentNumber(
        int $yearsAgo,
        int $sequence,
    ): string {
        $year = (int) new DateTime()->format('Y') - $yearsAgo;

        return $year . sprintf('%04d', $sequence);
    }
}
