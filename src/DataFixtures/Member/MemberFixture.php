<?php

declare(strict_types=1);

namespace App\DataFixtures\Member;

use App\Entity\Database\Address;
use App\Entity\Database\CheckoutSession;
use App\Entity\Database\Enums\CheckoutSessionStates;
use App\Entity\Database\Enums\MembershipTypes;
use App\Entity\Database\Enums\PostalRegions;
use App\Entity\Database\Enums\Studies;
use App\Entity\Database\Member as MemberModel;
use App\Entity\Database\Membership as MembershipModel;
use App\Entity\Database\ProspectiveMember;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Override;

use function count;
use function min;
use function sprintf;

/**
 * The members every other fixture builds on.
 *
 * Dates are derived from the moment of seeding rather than written out, so a membership history stays realistic
 * relative to the current association year however long ago the database was filled.
 */
class MemberFixture extends Fixture
{
    public const string REF_MEMBER_STUDENT = 'member-student';
    public const string REF_MEMBER_EXTERNAL = 'member-external';
    public const string REF_MEMBER_GRADUATE = 'member-graduate';
    public const string REF_MEMBER_PROSPECTIVE = 'member-prospective';

    /**
     * A deleted member. Without one, the `members_deleted` API permission is unobservable: every response looks the
     * same whether or not the calling principal holds it, and a regression that started leaking deleted members would
     * go unnoticed.
     */
    public const string REF_MEMBER_DELETED = 'member-deleted';

    /**
     * Members crafted to surface on the "members requiring attention" overview. The ones that have to count as active
     * organ members are referenced so {@see \App\DataFixtures\Decision\DecisionFixture} can install them.
     */
    public const string REF_MEMBER_ATTN_ORDINARY_ACTIVE = 'attn-ordinary-active';
    public const string REF_MEMBER_ATTN_EXTERNAL_ACTIVE = 'attn-external-active';
    public const string REF_MEMBER_ATTN_GRADUATE_ACTIVE = 'attn-graduate-active';
    public const string REF_MEMBER_ATTN_MISCLASSIFIED = 'attn-misclassified';

    #[Override]
    public function load(ObjectManager $manager): void
    {
        $this->loadProspective($manager);
        $this->loadPlainMembers($manager);
        $this->loadAttentionMembers($manager);

        $manager->flush();
    }

    private function loadProspective(ObjectManager $manager): void
    {
        $prospective = new ProspectiveMember();
        $prospective->setInitials('T.A.');
        $prospective->setFirstName('Tara');
        $prospective->setMiddleName('');
        $prospective->setLastName('Testdata');
        $prospective->setStudentNumber('1000012');
        $prospective->setBirth(new DateTime('2001-01-01'));
        $prospective->setEmail('tara@example.com');
        $prospective->setPaid(20);
        $prospective->setChangedOn(new DateTime());
        $prospective->setStudy(Studies::BAM);

        $address = new Address();
        $address->setStreet('Teststraat');
        $address->setNumber('123');
        $address->setPostalCode('5600 AA');
        $address->setCity('Eindhoven');
        $address->setPhone('1');
        $address->setCountry(PostalRegions::Netherlands);
        $prospective->setAddress($address);

        $manager->persist($prospective);
        $this->addReference(
            self::REF_MEMBER_PROSPECTIVE,
            $prospective,
        );

        $checkout = new CheckoutSession();
        $checkout->setCheckoutId('123');
        $checkout->setProspectiveMember($prospective);
        $checkout->setCreated(new DateTime());
        $checkout->setExpiration(new DateTime());
        $checkout->setState(CheckoutSessionStates::Paid);
        $manager->persist($checkout);
    }

    private function loadPlainMembers(ObjectManager $manager): void
    {
        $student = new MemberModel();
        $student->setInitials('T.');
        $student->setFirstName('Timon');
        $student->setMiddleName('de');
        $student->setLastName('Teststudent');
        $student->setEmail('timon@example.com');
        $student->setBirth(new DateTime('2000-01-01'));
        $student->setChangedOn(new DateTime());
        $student->setStudentNumber('1000020');
        $student->setStudy(Studies::BAM);
        $this->chainMemberships(
            $student,
            new DateTime('2018-08-14 midnight'),
        );
        $manager->persist($student);
        $this->addReference(
            self::REF_MEMBER_STUDENT,
            $student,
        );

        $external = new MemberModel();
        $external->setInitials('J.');
        $external->setFirstName('Joe');
        $external->setMiddleName('');
        $external->setLastName('Bloggs');
        $external->setEmail('joe@example.com');
        $external->setBirth(new DateTime('1999-01-01'));
        $external->setChangedOn(new DateTime());
        $external->setStudy(Studies::Other);
        $this->chainMemberships(
            $external,
            new DateTime('2017-08-15 midnight'),
            new DateTime('2020-06-30 midnight'),
            MembershipTypes::External,
        );
        $manager->persist($external);
        $this->addReference(
            self::REF_MEMBER_EXTERNAL,
            $external,
        );

        $graduate = new MemberModel();
        $graduate->setInitials('J.H.');
        $graduate->setFirstName('Jack');
        $graduate->setMiddleName('van');
        $graduate->setLastName('Lint');
        $graduate->setEmail('vanlint@example.com');
        $graduate->setBirth(new DateTime('1932-09-01'));
        $graduate->setChangedOn(new DateTime('1990-07-01'));
        $graduate->setStudy(Studies::None);
        $this->chainMemberships(
            $graduate,
            new DateTime('1989-08-15 midnight'),
            new DateTime('1994-06-30 midnight'),
            MembershipTypes::Graduate,
        );
        $manager->persist($graduate);
        $this->addReference(
            self::REF_MEMBER_GRADUATE,
            $graduate,
        );

        $deleted = new MemberModel();
        $deleted->setInitials('R.');
        $deleted->setFirstName('Rita');
        $deleted->setMiddleName('');
        $deleted->setLastName('Removed');
        $deleted->setEmail('rita@example.com');
        $deleted->setBirth(new DateTime('1998-01-01'));
        $deleted->setChangedOn(new DateTime());
        $deleted->setStudentNumber('1000030');
        $deleted->setStudy(Studies::BAM);
        $deleted->setDeleted(true);
        $this->chainMemberships(
            $deleted,
            new DateTime('2019-08-13 midnight'),
        );
        $manager->persist($deleted);
        $this->addReference(
            self::REF_MEMBER_DELETED,
            $deleted,
        );
    }

    /**
     * Fill a member's history with association years from `$start` until today, switching type on `$switchOn`.
     */
    private function chainMemberships(
        MemberModel $member,
        DateTime $start,
        ?DateTime $switchOn = null,
        ?MembershipTypes $afterSwitch = null,
    ): void {
        $startDate = $start;

        while ($startDate < new DateTime()) {
            $type = null !== $switchOn && null !== $afterSwitch && $startDate >= $switchOn
                ? $afterSwitch
                : MembershipTypes::Ordinary;

            $membership = new MembershipModel(
                member: $member,
                type: $type,
                startDate: clone $startDate,
                endDate: null,
            );
            $member->addMembership($membership);

            $startDate = $membership->getEndDate();
        }
    }

    /**
     * Members requiring attention.
     *
     * Histories are full association years following valid transitions. "Expiring soon" members end on the upcoming
     * July 1; the already-expired, boundary and out-of-window controls end a deliberate number of days from seed time,
     * so the day-based thresholds in the finders have something to catch and something to leave alone.
     */
    private function loadAttentionMembers(ObjectManager $manager): void
    {
        $now = new DateTime();
        $nextJul1 = new DateTime($now->format('Y') . '-07-01 midnight');

        if ($nextJul1 <= $now) {
            $nextJul1->modify('+1 year');
        }

        // A1: hidden and missing an e-mail address; must disappear once the finders filter hidden members.
        $this->makeAttentionMember(
            $manager,
            'H.',
            'Henk',
            'HiddenNoEmail',
            21,
            null,
            $this->studentNumber(2),
            Studies::BAM,
            $this->associationYearChain(
                4,
                $nextJul1,
            ),
            hidden: true,
        );

        // A2: a same-day membership (start equals end) is dropped silently, despite the missing address.
        $sameDay = new DateTime()->modify('-10 days');
        $this->makeAttentionMember(
            $manager,
            'S.',
            'Sanne',
            'SamedayDropped',
            21,
            null,
            $this->studentNumber(3),
            Studies::BAM,
            [
                [
                    MembershipTypes::Ordinary,
                    $sameDay,
                    clone $sameDay,
                ],
            ],
        );

        // A3: visible and missing an address — the control that stays once A1 is fixed.
        $this->makeAttentionMember(
            $manager,
            'V.',
            'Vera',
            'VisibleNoEmail',
            22,
            null,
            $this->studentNumber(4),
            Studies::BAM,
            $this->associationYearChain(
                3,
                $nextJul1,
            ),
        );

        // A4: an ordinary member without a student number.
        $this->makeAttentionMember(
            $manager,
            'N.',
            'Nora',
            'NoStudentId',
            22,
            'nostudentid@example.com',
            null,
            Studies::BAM,
            $this->associationYearChain(
                3,
                $nextJul1,
            ),
        );

        // B1: ordinary, active in an organ, expiring — the one with a renewal button.
        $this->addReference(
            self::REF_MEMBER_ATTN_ORDINARY_ACTIVE,
            $this->makeAttentionMember(
                $manager,
                'O.',
                'Olaf',
                'OrdinaryActive',
                22,
                'ordinaryactive@example.com',
                $this->studentNumber(5),
                Studies::BAM,
                $this->associationYearChain(
                    4,
                    $nextJul1,
                ),
            ),
        );

        // B2: ordinary, not active, expiring.
        $this->makeAttentionMember(
            $manager,
            'O.',
            'Otis',
            'OrdinaryNonActive',
            23,
            'ordinarynonactive@example.com',
            $this->studentNumber(6),
            Studies::BAM,
            $this->associationYearChain(
                4,
                $nextJul1,
            ),
        );

        // B3: external, active, expiring. Ordinary to external, because the board kept an active member on after
        // their studies ended.
        $this->addReference(
            self::REF_MEMBER_ATTN_EXTERNAL_ACTIVE,
            $this->makeAttentionMember(
                $manager,
                'E.',
                'Ellen',
                'ExternalActive',
                24,
                'externalactive@example.com',
                $this->studentNumber(7),
                Studies::Other,
                $this->associationYearChain(
                    6,
                    $nextJul1,
                    MembershipTypes::External,
                    3,
                ),
            ),
        );

        // B4: external, not active, expiring. Joined as an external — a PhD or a student from another department —
        // so external from the start rather than by conversion.
        $this->makeAttentionMember(
            $manager,
            'E.',
            'Evert',
            'ExternalNonActive',
            26,
            'externalnonactive@example.com',
            $this->studentNumber(8),
            Studies::Other,
            $this->associationYearChain(
                4,
                $nextJul1,
                MembershipTypes::External,
                4,
            ),
        );

        // B5: a graduate who is an inactive member of a fraternity. The graduate finder counts inactive organ members
        // as active, which is the whole point of this one.
        $this->addReference(
            self::REF_MEMBER_ATTN_GRADUATE_ACTIVE,
            $this->makeAttentionMember(
                $manager,
                'G.',
                'Gerda',
                'GraduateActive',
                31,
                'graduateactive@example.com',
                $this->studentNumber(9),
                Studies::None,
                $this->associationYearChain(
                    7,
                    $nextJul1,
                    MembershipTypes::Graduate,
                    2,
                ),
            ),
        );

        // B6: a graduate who is not active — never surfaces, because the graduate finder only includes active ones.
        // Reached graduate status the common way, as an external who stopped studying.
        $this->makeAttentionMember(
            $manager,
            'G.',
            'Gijs',
            'GraduateNonActive',
            32,
            'graduatenonactive@example.com',
            $this->studentNumber(10),
            Studies::None,
            $this->associationYearPhases(
                [
                    [
                        MembershipTypes::External,
                        4,
                    ],
                    [
                        MembershipTypes::Graduate,
                        2,
                    ],
                ],
                $nextJul1,
            ),
        );

        // C1: expired 30 days ago — labelled "expiring soon" with a date in the past.
        $this->makeAttentionMember(
            $manager,
            'P.',
            'Peter',
            'OrdinaryExpired30',
            23,
            'ordinaryexpired30@example.com',
            $this->studentNumber(11),
            Studies::BAM,
            $this->associationYearChain(
                4,
                new DateTime()->modify('-30 days'),
            ),
        );

        // C2: ended exactly 90 days ago at midnight — the off-by-a-few-hours boundary.
        $this->makeAttentionMember(
            $manager,
            'B.',
            'Bram',
            'OrdinaryBoundary90',
            23,
            'ordinaryboundary90@example.com',
            $this->studentNumber(12),
            Studies::BAM,
            $this->associationYearChain(
                4,
                new DateTime()->modify('-90 days'),
            ),
        );

        // C3: expired 180 days ago — the control beyond the window, which must not appear.
        $this->makeAttentionMember(
            $manager,
            'L.',
            'Lotte',
            'OrdinaryExpired180',
            24,
            'ordinaryexpired180@example.com',
            $this->studentNumber(13),
            Studies::BAM,
            $this->associationYearChain(
                4,
                new DateTime()->modify('-180 days'),
            ),
        );

        // C4: expiring in 180 days — the control beyond the other end of the window.
        $this->makeAttentionMember(
            $manager,
            'F.',
            'Freek',
            'OrdinaryFuture180',
            22,
            'ordinaryfuture180@example.com',
            $this->studentNumber(14),
            Studies::BAM,
            $this->associationYearChain(
                4,
                new DateTime()->modify('+180 days'),
            ),
        );

        // C5: an external who expired 30 days ago, so the past-expiry case is covered for externals too.
        $this->makeAttentionMember(
            $manager,
            'X.',
            'Xander',
            'ExternalExpired30',
            26,
            'externalexpired30@example.com',
            $this->studentNumber(15),
            Studies::Other,
            $this->associationYearChain(
                4,
                new DateTime()->modify('-30 days'),
                MembershipTypes::External,
                4,
            ),
        );

        // D1: expired 30 days ago, active in an organ at that point, discharged 10 days ago. The finder reads
        // activity as of today and therefore files them as non-active.
        $this->addReference(
            self::REF_MEMBER_ATTN_MISCLASSIFIED,
            $this->makeAttentionMember(
                $manager,
                'M.',
                'Marit',
                'Misclassified',
                26,
                'misclassified@example.com',
                $this->studentNumber(16),
                Studies::BAM,
                $this->associationYearChain(
                    4,
                    new DateTime()->modify('-30 days'),
                ),
            ),
        );

        // E1: a master who joined in February rather than at the introduction week, so their first membership runs
        // from February to the next July.
        $this->makeAttentionMember(
            $manager,
            'I.',
            'Iris',
            'SpringMaster',
            24,
            'springmaster@example.com',
            $this->studentNumber(17),
            Studies::MCSE,
            $this->associationYearChain(
                2,
                $nextJul1,
                joinMonth: 2,
                joinDay: 10,
            ),
        );

        // E2: a PhD who joined in November.
        $this->makeAttentionMember(
            $manager,
            'D.',
            'Daan',
            'AutumnExternal',
            27,
            'autumnexternal@example.com',
            $this->studentNumber(18),
            Studies::PhDCS,
            $this->associationYearChain(
                3,
                $nextJul1,
                MembershipTypes::External,
                3,
                joinMonth: 11,
                joinDay: 1,
            ),
        );
    }

    /**
     * @param list<array{0: MembershipTypes, 1: DateTime, 2: DateTime}> $segments
     */
    private function makeAttentionMember(
        ObjectManager $manager,
        string $initials,
        string $firstName,
        string $lastName,
        int $ageInYears,
        ?string $email,
        ?string $studentNumber,
        Studies $study,
        array $segments,
        bool $hidden = false,
    ): MemberModel {
        $member = new MemberModel();
        $member->setInitials($initials);
        $member->setFirstName($firstName);
        $member->setMiddleName('');
        $member->setLastName($lastName);
        $member->setBirth(new DateTime()->modify('-' . $ageInYears . ' years'));
        $member->setChangedOn(new DateTime());
        $member->setStudy($study);

        if (null !== $email) {
            $member->setEmail($email);
        }

        if (null !== $studentNumber) {
            $member->setStudentNumber($studentNumber);
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
     * A chain of association-year memberships for someone who has been around `$years` years. The last
     * `$finalTypeYears` of them use `$finalType`, which models a single transition such as ordinary to external.
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
        $finalTypeYears = min(
            $finalTypeYears,
            $years,
        );
        $ordinaryYears = $years - $finalTypeYears;

        $phases = [];

        if ($ordinaryYears > 0) {
            $phases[] = [
                MembershipTypes::Ordinary,
                $ordinaryYears,
            ];
        }

        $phases[] = [
            $finalType,
            $finalTypeYears,
        ];

        return $this->associationYearPhases(
            $phases,
            $finalEnd,
            $joinMonth,
            $joinDay,
        );
    }

    /**
     * The same, from a list of `[type, years]` phases, for a history with more than one transition. Memberships run
     * from July 1 to July 1; the first starts on the join date and the last ends on `$finalEnd`.
     *
     * @param list<array{0: MembershipTypes, 1: int}> $phases
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
        // An August join falls in the first calendar year of the association year; anything earlier in the next.
        $firstYear = $joinMonth >= 7
            ? $joinYear
            : $joinYear + 1;

        $segments = [];

        for ($i = 0; $i < $count; $i++) {
            $start = 0 === $i
                ? new DateTime(sprintf('%d-%02d-%02d 00:00:00', $firstYear, $joinMonth, $joinDay))
                : new DateTime(($joinYear + $i) . '-07-01 midnight');
            $end = $i === $count - 1
                ? clone $finalEnd
                : new DateTime(($joinYear + $i + 1) . '-07-01 midnight');

            $segments[] = [
                $types[$i],
                $start,
                $end,
            ];
        }

        return $segments;
    }

    /**
     * The latest July 1 strictly before the given date.
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
     * A TU/e student number: seven digits satisfying the elfproef. The sequence starts at 2, because the members
     * above use hardcoded numbers from the same block. A prefix whose check digit would be 10 is skipped.
     */
    private function studentNumber(int $sequence): string
    {
        $prefix = 100000 + $sequence * 10;

        do {
            $digits = sprintf(
                '%06d',
                $prefix,
            );
            $sum = 0;

            for ($position = 0; $position < 6; $position++) {
                $sum += (7 - $position) * (int) $digits[$position];
            }

            $checkDigit = (11 - $sum % 11) % 11;
            $prefix++;
        } while (10 === $checkDigit);

        return $digits . $checkDigit;
    }
}
