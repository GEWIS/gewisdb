<?php

declare(strict_types=1);

namespace DatabaseTest\Seeder;

use Application\Model\Enums\MembershipTypes;
use Application\Model\Enums\PostalRegions;
use Database\Model\Address;
use Database\Model\CheckoutSession;
use Database\Model\Enums\CheckoutSessionStates;
use Database\Model\Member;
use Database\Model\ProspectiveMember;
use DateTime;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class MemberFixture extends AbstractFixture
{
    public const REF_MEMBER_STUDENT = 'student';
    public const REF_MEMBER_EXTERNAL = 'external';
    public const REF_MEMBER_GRADUATE = 'graduate';
    public const REF_MEMBER_PROSPECTIVE = 'prospective';

    public function load(ObjectManager $manager): void
    {
        $expiryDate = new DateTime();
        $expiryDate->setDate((int) $expiryDate->format('Y') + 1, 7, 1);

        /** Prospective member */
            $pros = new ProspectiveMember();
            $pros->setInitials('T.A.');
            $pros->setFirstName('Tara');
            $pros->setMiddleName('');
            $pros->setLastName('Testdata');
            $pros->setTueUsername('20190001');
            $pros->setBirth(new DateTime('2001-01-01'));
            $pros->setEmail('tara@gewisdb.local');
            $pros->setPaid(15);
            $pros->setChangedOn(new DateTime());
            $prosAddress = new Address();
            $prosAddress->setStreet('Teststraat');
            $prosAddress->setNumber('123');
            $prosAddress->setPostalCode('5600 AA');
            $prosAddress->setCity('Eindhoven');
            $prosAddress->setPhone('1');
            $prosAddress->setCountry(PostalRegions::Netherlands);
            $pros->setAddress($prosAddress);
            $pros->setStudy('Other');

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
            $student = new Member();
            $student->setInitials('T.');
            $student->setFirstName('Timon');
            $student->setMiddleName('de');
            $student->setLastName('Teststudent');
            $student->setEmail('timon@gewisdb.local');
            $student->setBirth(new DateTime('2000-01-01'));
            $student->setGeneration(2018);
            $student->setType(MembershipTypes::Ordinary);
            $student->setExpiration($expiryDate);
            $student->setChangedOn(new DateTime());
            $student->setTueUsername('20180001');
            $student->setIsStudying(true);

            $manager->persist($student);
            $this->addReference(self::REF_MEMBER_STUDENT, $student);

        /** External */
            $external = new Member();
            $external->setInitials('J.');
            $external->setFirstName('Joe');
            $external->setMiddleName('');
            $external->setLastName('Bloggs');
            $external->setEmail('joe@gewisdb.local');
            $external->setBirth(new DateTime('1999-01-01'));
            $external->setGeneration(2017);
            $external->setType(MembershipTypes::External);
            $external->setExpiration($expiryDate);
            $external->setChangedOn(new DateTime());
            $external->setIsStudying(false);

            $manager->persist($external);
            $this->addReference(self::REF_MEMBER_EXTERNAL, $external);

        /** Graduate */
            $graduate = new Member();
            $graduate->setInitials('J.H.');
            $graduate->setFirstName('Jack');
            $graduate->setMiddleName('van');
            $graduate->setLastName('Lint');
            $graduate->setEmail('vanlint@gewisdb.local');
            $graduate->setBirth(new DateTime('1932-09-01'));
            $graduate->setGeneration(1989);
            $graduate->setType(MembershipTypes::Graduate);
            $graduate->setExpiration($expiryDate);
            $graduate->setMembershipEndsOn(new DateTime());
            $graduate->setChangedOn(new DateTime('1990-07-01'));
            $graduate->setIsStudying(false);

            $manager->persist($graduate);
            $this->addReference(self::REF_MEMBER_GRADUATE, $graduate);

        $manager->flush();
    }
}
