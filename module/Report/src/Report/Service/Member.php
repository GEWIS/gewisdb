<?php

namespace Report\Service;

use Application\Service\AbstractService;

use Database\Model\Member as DbMember;
use Database\Model\Address as DbAddress;
use Report\Model\Member as ReportMember;
use Report\Model\Address as ReportAddress;

class Member extends AbstractService
{

    /**
     * Export members.
     */
    public function generate()
    {
        $mapper = $this->getMemberMapper();

        $em = $this->getServiceManager()->get('doctrine.entitymanager.orm_report');
        $repo = $em->getRepository('Report\Model\Member');

        foreach ($mapper->findAll() as $member) {
            // first try to find an existing member
            $reportMember = $repo->find($member->getLidnr());

            if ($reportMember == null) {
                $reportMember = new ReportMember();
            }

            $reportMember->setLidnr($member->getLidnr());
            $reportMember->setEmail($member->getEmail());
            $reportMember->setLastName($member->getLastName());
            $reportMember->setMiddleName($member->getMiddleName());
            $reportMember->setInitials($member->getInitials());
            $reportMember->setFirstName($member->getFirstName());
            $reportMember->setGender($member->getGender());
            $reportMember->setGeneration($member->getGeneration());
            $reportMember->setType($member->getType());
            $reportMember->setExpiration($member->getExpiration());
            $reportMember->setBirth($member->getBirth());
            $reportMember->setChangedOn($member->getChangedOn());
            $reportMember->setPaid($member->getPaid());

            // go through addresses
            $em->persist($reportMember);
        }
        $em->flush();
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
     * Get the console object.
     */
    public function getConsole()
    {
        return $this->getServiceManager()->get('console');
    }
}
