<?php

declare(strict_types=1);

namespace DatabaseTest\Seeder;

use Database\Model\SavedQuery;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class SavedQueriesFixture extends AbstractFixture
{
    public const REF_QUERY_UNDERAGE = 'query_underage';
    public const REF_QUERY_MEMBERDETAILS = 'query_memberdetails';

    public function load(ObjectManager $manager): void
    {
        // Query for underaged members
        $query = new SavedQuery();
        $query->setCategory('BAC/BHV');
        $query->setName('Underage members (18-)');
        $query->setQuery(<<<'STR'
            SELECT m FROM db:Member as m
            WHERE DATE_ADD(m.birth, 216, 'MONTH') > CURRENT_DATE() AND m.generation >= YEAR(CURRENT_DATE()) - 18
            ORDER BY m.birth
            STR);

        $manager->persist($query);
        $this->addReference(self::REF_QUERY_UNDERAGE, $query);

        // Get membership details based on membership numbers
        // Used e.g. for (GMM) attendance lists
        $query = new SavedQuery();
        $query->setCategory('Secretary');
        $query->setName('Get member details based on membership number');
        $query->setQuery(<<<'STR'
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
            STR);

        $manager->persist($query);
        $this->addReference(self::REF_QUERY_MEMBERDETAILS, $query);

        $manager->flush();
    }
}
