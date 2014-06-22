<?php

namespace Import\Service;

use Application\Service\AbstractService;

use Database\Model\Member as MemberModel;
use Database\Model\Address;

class Member extends AbstractService
{

    /**
     * Get members.
     *
     * @return array
     */
    public function getMembers()
    {
        return $this->getQuery()->fetchMembers();
    }

    /**
     * Import a member.
     *
     * @param array $data
     */
    public function import($data)
    {
        $member = new MemberModel();

        $member->setLidnr($data['lidnummer']);
        $member->setEmail($data['e_mail']);
        $member->setLastName($data['achternaam']);
        $member->setMiddleName($data['tussen']);
        $member->setInitials($data['voorlet']);
        $member->setFirstName($data['voornaam']);

        $gender = $data['gesl'] ? MemberModel::GENDER_MALE : MemberModel::GENDER_FEMALE;
        $member->setGender($gender);

        $member->setGeneration($data['generatie']);
        $member->setExpiration(new \DateTime($data['verloopdatum']));
        $member->setBirth(new \DateTime($data['geboortedatum']));

        switch (strtolower($data['lidsoort'])) {
        case 'basis lid':
            $member->setType(MemberModel::TYPE_ORDINARY);
            break;
        case 'geprolongeerd lid':
            $member->setType(MemberModel::TYPE_PROLONGED);
            break;
        case 'extern lid':
            $member->setType(MemberModel::TYPE_EXTERNAL);
            break;
        case 'buitengewoon lid':
            $member->setType(MemberModel::TYPE_EXTRAORDINARY);
            break;
        case 'erelid':
            $member->setType(MemberModel::TYPE_HONORARY);
            break;
        }

        // import addresses
        if (!empty($data['hstraat'])) {
            $home = new Address();
            $home->setType(Address::TYPE_HOME);

            // no other ones
            $home->setCountry('Nederland');

            // separate the street and the number + suffix
            if (preg_match('/^(.*) ([0-9]+[a-zA-Z]*)$/i', trim($data['hstraat']), $matches)) {
                $home->setStreet($matches[1]);
                $home->setNumber($matches[2]);
            } else {
                // we don't have anything better than this
                $home->setStreet($data['hstraat']);
                $home->setNumber(0);
            }

            $home->setPostalCode($data['hpostcode']);
            $home->setCity($data['hplaats']);
            $home->setPhone($data['htelefoon']);

            $member->addAddress($home);
        }
        if (!empty($data['kstraat'])) {
            $student = new Address();
            $student->setType(Address::TYPE_STUDENT);

            // no other countries
            $student->setCountry('Nederland');

            // separate the street and the number + suffix
            if (preg_match('/^(.*) ([0-9]+[a-zA-Z]*)$/i', trim($data['kstraat']), $matches)) {
                $student->setStreet($matches[1]);
                $student->setNumber($matches[2]);
            } else {
                // we don't have anything better than this
                $student->setStreet($data['kstraat']);
                $student->setNumber(0);
            }

            $student->setPostalCode($data['kpostcode']);
            $student->setCity($data['kplaats']);
            $student->setPhone($data['ktelefoon']);

            $member->addAddress($student);
        }

        echo 'Imported ' . $member->getFullName() . ' (' . $member->getLidnr() . ")\n";
    }

    /**
     * Get the query object.
     */
    public function getQuery()
    {
        return $this->getServiceManager()->get('import_database_query');
    }

    /**
     * Get the console object.
     */
    public function getConsole()
    {
        return $this->getServiceManager()->get('console');
    }
}
