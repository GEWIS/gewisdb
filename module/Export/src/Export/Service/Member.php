<?php

namespace Export\Service;

use Application\Service\AbstractService;

use Database\Model\Member as MemberModel;
use Database\Model\Address;

class Member extends AbstractService
{

    /**
     * Export members.
     */
    public function export()
    {
        $mapper = $this->getMemberMapper();

        foreach ($mapper->findAll() as $member) {

            $haddr = array(
                'straat' => '',
                'postcode' => '',
                'plaats' => '',
                'telefoon' => ''
            );
            $kaddr = array(
                'straat' => '',
                'postcode' => '',
                'plaats' => '',
                'telefoon' => ''
            );

            foreach ($member->getAddresses() as $address) {
                if ($address->getType() == Address::TYPE_HOME) {
                    $haddr['straat'] = $address->getStreet() . ' ' . $address->getNumber();
                    $haddr['postcode'] = $address->getPostalCode();
                    $haddr['plaats'] = $address->getCity();
                    $haddr['telefoon'] = $address->getPhone();
                } else if ($address->getType() == Address::TYPE_STUDENT) {
                    $kaddr['straat'] = $address->getStreet() . ' ' . $address->getNumber();
                    $kaddr['postcode'] = $address->getPostalCode();
                    $kaddr['plaats'] = $address->getCity();
                    $kaddr['telefoon'] = $address->getPhone();
                }
            }

            $data = array(
                'lidnummer' => $member->getLidnr(),
                'achternaam' => $member->getLastName(),
                'tussen' => $member->getMiddleName(),
                'voorlet' => $member->getInitials(),
                'voornaam' => $member->getFirstName(),
                'gesl' => $member->getGender() == MemberModel::GENDER_MALE ? '1': '0',
                'generatie' => $member->getGeneration(),
                'e_mail' => $member->getEmail(),
                'betaald' => $member->getPaid(),
                'lidsoortid' => null,
                'verloopdatum' => $member->getExpiration()->format('Y-m-d'),
                'geboortedatum' => $member->getBirth()->format('Y-m-d'),
                // thuis
                'hstraat' => $haddr['straat'],
                'hpostcode' => $haddr['postcode'],
                'hplaats' => $haddr['plaats'],
                'htelefoon' => $haddr['telefoon'],
                // kamer
                'kstraat' => $kaddr['straat'],
                'kpostcode' => $kaddr['postcode'],
                'kplaats' => $kaddr['plaats'],
                'ktelefoon' => $kaddr['telefoon'],
            );

            switch ($member->getType()) {
            case MemberModel::TYPE_ORDINARY:
                $data['lidsoortid'] = 1;
                break;
            case MemberModel::TYPE_PROLONGED:
                $data['lidsoortid'] = 2;
                break;
            case MemberModel::TYPE_EXTERNAL:
                $data['lidsoortid'] = 3;
                break;
            case MemberModel::TYPE_EXTRAORDINARY:
                $data['lidsoortid'] = 4;
                break;
            case MemberModel::TYPE_HONORARY:
                $data['lidsoortid'] = 5;
                break;
            }

            // first check if it is an existing member
            if ($this->getQuery()->checkMemberExists($member->getLidnr())) {
                $this->getQuery()->updateMember($data);
                //echo "Lid " . $member->getFullName() . " is geupdate.\n";
            } else {
                echo "Nog niet bestaand: " . $member->getFullName() . "\n";
                $this->getQuery()->createMember($data);
            }
        }
    }

    /**
     * Get the member mapper.
     *
     * @return \Database\Mapper\Member
     */
    public function getMemberMapper()
    {
        return $this->getServiceManager()->get('database_mapper_member');
    }

    /**
     * Get the query object.
     */
    public function getQuery()
    {
        return $this->getServiceManager()->get('export_query_member');
    }

    /**
     * Get the console object.
     */
    public function getConsole()
    {
        return $this->getServiceManager()->get('console');
    }
}
