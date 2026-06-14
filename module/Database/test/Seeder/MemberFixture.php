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

/**
 * phpcs:disable Generic.WhiteSpace.ScopeIndent.IncorrectExact
 */
class MemberFixture extends AbstractFixture
{
    public const string REF_MEMBER_STUDENT = 'student';
    public const string REF_MEMBER_EXTERNAL = 'external';
    public const string REF_MEMBER_GRADUATE = 'graduate';
    public const string REF_MEMBER_PROSPECTIVE = 'prospective';

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

        $manager->flush();
    }
}
