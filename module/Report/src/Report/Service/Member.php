<?php

namespace Report\Service;

use Application\Service\AbstractService;

use Database\Model\Member as DbMember;
use Database\Model\Address as DbAddress;
use Report\Model\Member as ReportMember;
use Report\Model\Address as ReportAddress;
use Zend\Cache\Exception\LogicException;

class Member extends AbstractService
{

    /**
     * Export members.
     */
    public function generate()
    {
        $mapper = $this->getMemberMapper();

        $em = $this->getServiceManager()->get('doctrine.entitymanager.orm_report');

        foreach ($mapper->findAll() as $member) {
            $this->generateMember($member);
        }
        $em->flush();
    }

    public function generateMember($member)
    {
        $em = $this->getServiceManager()->get('doctrine.entitymanager.orm_report');
        $repo = $em->getRepository('Report\Model\Member');
        // first try to find an existing member
        $reportMember = $repo->find($member->getLidnr());

        if (null === $reportMember) {
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
        $reportMember->setIban($member->getIban());
        $reportMember->setSupremum($member->getSupremum());

        // go through addresses
        foreach ($member->getAddresses() as $address) {
            $this->generateAddress($address, $reportMember);
        }

        // process mailing lists
        $this->generateLists($member, $reportMember);
        $em->persist($reportMember);
    }

    public function generateLists($member, $reportMember)
    {
        $em = $this->getServiceManager()->get('doctrine.entitymanager.orm_report');
        $reportListRepo = $em->getRepository('Report\Model\MailingList');

        foreach ($member->getLists() as $list) {
            $reportList = $reportListRepo->find($list->getName());

            if (null === $reportList) {
                throw new LogicException('mailing list missing from reportdb');
            }

            // check if in the list
            $func = function ($carry, $lst) use ($reportList) {
                return $carry || ($lst->getName() == $reportList->getName());
            };
            if (!array_reduce($reportMember->getLists()->toArray(), $func, false)) {
                $reportMember->addList($reportList);
            }

            $em->persist($reportList);
        }
    }

    public function generateAddress($address, $reportMember = null)
    {
        $em = $this->getServiceManager()->get('doctrine.entitymanager.orm_report');
        $addrRepo = $em->getRepository('Report\Model\Address');
        if ($reportMember === null) {
            $reportMember = $em->getRepository('Report\Model\Member')->find($address->getMember()->getLidnr());
            if ($reportMember === null) {
                throw new \LogicException('Address without member');
            }
        }
        $reportAddress = $addrRepo->find(array(
            'member' => $reportMember->getLidnr(),
            'type' => $address->getType()
        ));
        if (null === $reportAddress) {
            $reportAddress = new ReportAddress();
        }
        $reportAddress->setType($address->getType());
        $reportAddress->setCountry($address->getCountry());
        $reportAddress->setStreet($address->getStreet());
        $reportAddress->setNumber($address->getNumber());
        $reportAddress->setPostalCode($address->getPostalCode());
        $reportAddress->setCity($address->getCity());
        $reportAddress->setPhone($address->getPhone());
        $reportMember->addAddress($reportAddress);
        $em->persist($reportAddress);
    }

    public function deleteMember($member)
    {
        $em = $this->getServiceManager()->get('doctrine.entitymanager.orm_report');
        $repo = $em->getRepository('Report\Model\Member');
        // first try to find an existing member
        $reportMember = $repo->find($member->getLidnr());
        $em->remove($reportMember);
    }

    public function deleteAddress($address)
    {
        $em = $this->getServiceManager()->get('doctrine.entitymanager.orm_report');
        $repo = $em->getRepository('Report\Model\Address');
        // first try to find an existing member
        $reportAddress = $repo->find(array(
            'member' => $address->getMember()->getLidnr(),
            'type' => $address->getType()
        ));
        $em->remove($reportAddress);
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
