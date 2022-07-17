<?php

namespace Report\Service;

use Database\Mapper\Member as MemberMapper;
use Doctrine\ORM\EntityManager;
use Laminas\ProgressBar\Adapter\Console;
use Laminas\ProgressBar\ProgressBar;
use LogicException;
use Report\Model\Member as ReportMember;
use Report\Model\Address as ReportAddress;

class Member
{
    /** @var MemberMapper $memberMapper */
    private $memberMapper;

    /** @var EntityManager $emReport */
    private $emReport;

    /**
     * @param MemberMapper $memberMapper
     * @param EntityManager $emReport
     */
    public function __construct(
        MemberMapper $memberMapper,
        EntityManager $emReport,
    ) {
        $this->memberMapper = $memberMapper;
        $this->emReport = $emReport;
    }

    /**
     * Export members.
     */
    public function generate()
    {
        $memberCollection = $this->memberMapper->findAll();

        $adapter = new Console();
        $progress = new ProgressBar($adapter, 0, count($memberCollection));

        $num = 0;
        foreach ($memberCollection as $member) {
            if ($num++ % 20 == 0) {
                $this->emReport->flush();
                $this->emReport->clear();
                $progress->update($num);
            }

            $this->generateMember($member);
        }

        $this->emReport->flush();
        $this->emReport->clear();
        $progress->finish();
    }

    public function generateMember($member)
    {
        $repo = $this->emReport->getRepository('Report\Model\Member');
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
        $reportMember->setMembershipEndsOn($member->getMembershipEndsOn());
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
        $this->emReport->persist($reportMember);
    }

    public function generateLists($member, $reportMember)
    {
        $reportListRepo = $this->emReport->getRepository('Report\Model\MailingList');

        $reportLists = array_map(function ($list) {
            return $list->getName();
        }, $reportMember->getLists()->toArray());
        $lists = array_map(function ($list) {
            return $list->getName();
        }, $member->getLists()->toArray());

        foreach (array_diff($lists, $reportLists) as $list) {
            $reportList = $reportListRepo->find($list);

            if (null === $reportList) {
                throw new LogicException('mailing list missing from reportdb');
            }

            $reportMember->addList($reportList);
            $this->addToMailmanList($member, $list);
            $this->emReport->persist($reportList);
        }

        foreach (array_diff($reportLists, $lists) as $list) {
            $reportList = $reportListRepo->find($list);

            if (null === $reportList) {
                throw new LogicException('mailing list missing from reportdb');
            }

            $reportMember->removeList($reportList);
            $this->removeFromMailmanList($member, $list);
            $this->emReport->persist($reportList);
        }
    }

    public function generateAddress($address, $reportMember = null)
    {
        $addrRepo = $this->emReport->getRepository('Report\Model\Address');

        if ($reportMember === null) {
            $reportMember = $this->emReport->getRepository('Report\Model\Member')->find($address->getMember()->getLidnr());
            if ($reportMember === null) {
                throw new LogicException('Address without member');
            }
        }

        $reportAddress = $addrRepo->find([
            'member' => $reportMember->getLidnr(),
            'type' => $address->getType(),
        ]);

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
        $this->emReport->persist($reportAddress);
    }

    public function deleteMember($member)
    {
        $repo = $this->emReport->getRepository('Report\Model\Member');
        // first try to find an existing member
        $reportMember = $repo->find($member->getLidnr());
        $this->emReport->remove($reportMember);
    }

    public function deleteAddress($address)
    {
        $repo = $this->emReport->getRepository('Report\Model\Address');

        // first try to find an existing member
        $reportAddress = $repo->find([
            'member' => $address->getMember()->getLidnr(),
            'type' => $address->getType(),
        ]);

        $this->emReport->remove($reportAddress);
    }

    public function addToMailmanList($member, $listName)
    {
        // TODO
    }

    public function removeFromMailmanList($member, $listName)
    {
        // TODO
    }
}
