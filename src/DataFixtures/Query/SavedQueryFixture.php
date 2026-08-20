<?php

declare(strict_types=1);

namespace App\DataFixtures\Query;

use App\Entity\Database\SavedQuery;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Override;

/**
 * The stored queries the query console opens with, so it has something to run before anyone has written one.
 */
class SavedQueryFixture extends Fixture
{
    public const string REF_QUERY_UNDERAGE = 'query-underage';
    public const string REF_QUERY_MEMBER_DETAILS = 'query-member-details';

    #[Override]
    public function load(ObjectManager $manager): void
    {
        $underage = new SavedQuery();
        $underage->setCategory('BAC/BHV');
        $underage->setName('Underage members (18-)');
        $underage->setQuery(<<<'DQL'
            SELECT m FROM db:Member as m
            WHERE DATE_ADD(m.birth, 216, 'MONTH') > CURRENT_DATE() AND m.generation >= YEAR(CURRENT_DATE()) - 18
            ORDER BY m.birth
            DQL);
        $manager->persist($underage);
        $this->addReference(
            self::REF_QUERY_UNDERAGE,
            $underage,
        );

        // Used for attendance lists at a GMM, among other things.
        $details = new SavedQuery();
        $details->setCategory('Secretary');
        $details->setName('Get member details based on membership number');
        $details->setQuery(<<<'DQL'
            SELECT DISTINCT
                m.lidnr,
                m.email,
                m.birth,
                m.generation,
                (CASE
                    WHEN m.middleName IS NULL THEN CONCAT(m.firstName, ' ', m.lastName)
                    ELSE CONCAT(m.firstName, ' ', m.middleName, ' ', m.lastName)
                    END) AS name,
                a.street,
                a.number,
                a.postalCode,
                a.city,
                a.country,
                a.type,
                m.supremum
            FROM db:Member AS m
                INNER JOIN db:Address AS a WITH a.member = m
                LEFT JOIN db:OrganMember AS o WITH o.member = m
            WHERE (m.lidnr in (9006, 9093))
                        AND ((a.type = 'home' AND NOT EXISTS(SELECT mad
                                FROM db:Member as mad
                                LEFT JOIN mad.addresses AS ad
                                WHERE ad.type = 'student' AND mad.lidnr = m.lidnr))
                            OR (a.type = 'student' AND NOT EXISTS(SELECT mads
                                FROM db:Member as mads
                                LEFT JOIN mads.addresses AS ads
                                WHERE ads.type = 'mail' AND mads.lidnr = m.lidnr))
                            OR (a.type = 'mail'))
            ORDER BY m.lidnr ASC
            DQL);
        $manager->persist($details);
        $this->addReference(
            self::REF_QUERY_MEMBER_DETAILS,
            $details,
        );

        $manager->flush();
    }
}
