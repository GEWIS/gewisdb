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
            //echo "Exporting " . $member->getFullName() . "\n";
            // TODO: export member

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
                    $haddr['straat'] = $address->getStreet();
                    $haddr['postcode'] = $address->getPostalCode();
                    $haddr['plaats'] = $address->getCity();
                    $haddr['telefoon'] = $address->getPhone();
                } else if ($address->getType() == Address::TYPE_STUDENT) {
                    $kaddr['straat'] = $address->getStreet();
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
                // TODO 'betaald' => 'TODO',
                // TODO 'lidsoortid' => 'TODO',
                'verloopdatum' => $member->getExpiration()->format('Y-m-d'),
                'geboortedatum' => $member->getExpiration()->format('Y-m-d'),
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
                // mail settings
                // TODO 'direct' => 'TODO',
                // TODO 'plijst' => 'TODO',
                // TODO 'winlijst' => 'TODO',
                // TODO 'gewislijst' => 'TODO',
                // TODO 'vacature' => 'TODO',
                // TODO 'babbel' => 'TODO',
            );

            $this->saveMember($data);
        }
    }

    /**
     * Save an old member.
     *
     * @param $data Data to save
     */
    public function saveMember($data)
    {
        // first check if it is an existing member
        if ($this->getQuery()->checkMemberExists($data['lidnummer'])) {
            echo "Lid " . $data['lidnummer'] . " bestaat al.\n";
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
