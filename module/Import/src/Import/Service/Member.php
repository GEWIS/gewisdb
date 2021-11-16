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

        $keys = array('e_mail', 'achternaam', 'tussen', 'voorlet', 'voornaam',
            'hstraat', 'hpostcode', 'hplaats', 'htelefoon',
            'kstraat', 'kpostcode', 'kplaats', 'ktelefoon', 'betaald',
            'direct', 'plijst', 'winlijst', 'gewislijst', 'vacature', 'babbel'
        );
        foreach ($keys as $key) {
            if (empty($data[$key])) {
                $data[$key] = '';
            }
        }

        $member->setLidnr($data['lidnummer']);
        $member->setEmail($data['e_mail']);
        $member->setLastName($data['achternaam']);
        $member->setMiddleName($data['tussen']);
        $member->setInitials($data['voorlet']);
        $member->setFirstName($data['voornaam']);
        if (!is_numeric($data['betaald'])) {
            $data['betaald'] = 0;
        }
        $member->setPaid($data['betaald']);

        $gender = $data['gesl'] ? MemberModel::GENDER_MALE : MemberModel::GENDER_FEMALE;
        $member->setGender($gender);

        $member->setGeneration($data['generatie']);
        $member->setBirth(new \DateTime($data['geboortedatum']));

        // use the old expiration date to calculate the changedon date
        $changed = new \DateTime($data['verloopdatum']);

        switch (strtolower($data['lidsoort'])) {
        case 'basis lid':
            $member->setType(MemberModel::TYPE_ORDINARY);
            $changed->sub(new \DateInterval('P6Y'));
            break;
        case 'geprolongeerd lid':
            $member->setType(MemberModel::TYPE_EXTERNAL);
            $changed->sub(new \DateInterval('P1Y'));
            break;
        case 'extern lid':
            $member->setType(MemberModel::TYPE_EXTERNAL);
            $changed->sub(new \DateInterval('P1Y'));
            break;
        case 'buitengewoon lid':
            $member->setType(MemberModel::TYPE_EXTERNAL);
            $changed->sub(new \DateInterval('P1Y'));
            break;
        case 'erelid':
            $member->setType(MemberModel::TYPE_HONORARY);
            $changed = new \DateTime();
            break;
        }

        $member->setChangedOn($changed);

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

        // import mailing list subscriptions
        $mlService = $this->getServiceManager()->get('database_service_mailinglist');
        if ($data['direct']) {
            $list = $mlService->getList('direct');
            if (null !== $list) {
                $member->addList($list);
            }
        }
        if ($data['plijst']) {
            $list = $mlService->getList('p-lijst');
            if (null !== $list) {
                $member->addList($list);
            }
        }
        if ($data['winlijst']) {
            $list = $mlService->getList('win-lijst');
            if (null !== $list) {
                $member->addList($list);
            }
        }
        if ($data['gewislijst']) {
            $list = $mlService->getList('gewis-lijst');
            if (null !== $list) {
                $member->addList($list);
            }
        }
        if ($data['vacature']) {
            $list = $mlService->getList('vacature-l');
            if (null !== $list) {
                $member->addList($list);
            }
        }
        if ($data['babbel']) {
            $list = $mlService->getList('babbel');
            if (null !== $list) {
                $member->addList($list);
            }
        }

        $em = $this->getServiceManager()->get('database_doctrine_em');

        $metadata = $em->getClassMetaData(get_class($member));
        $metadata->setIdGenerator(new \Doctrine\ORM\Id\AssignedGenerator());

        $em->persist($member);

        // prevent lidnr automatic generation

        echo 'Imported ' . $member->getFullName() . ' (' . $member->getLidnr() . ")\n";
    }

    /**
     * Flush the entity manager.
     */
    public function flush()
    {
        $em = $this->getServiceManager()->get('database_doctrine_em');
        $em->flush();
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
