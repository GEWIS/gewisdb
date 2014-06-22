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
        var_dump($data);

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

        var_dump($member);
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
