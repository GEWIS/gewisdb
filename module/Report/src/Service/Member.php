<?php

namespace Report\Service;

use Database\Mapper\Member as MemberMapper;
use Database\Model\{
    Address as DatabaseAddressModel,
    Member as DatabaseMemberModel,
};
use Doctrine\ORM\EntityManager;
use Laminas\ProgressBar\Adapter\Console;
use Laminas\ProgressBar\ProgressBar;
use LogicException;
use Report\Model\{
    MailingList as ReportMailingListModel,
    Member as ReportMemberModel,
    Address as ReportAddressModel,
};

class Member
{
    public function __construct(
        private readonly MemberMapper $memberMapper,
        private readonly EntityManager $emReport,
    ) {
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
        /** @var DatabaseMemberModel $member */
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

    public function generateMember(DatabaseMemberModel $member)
    {
        $repo = $this->emReport->getRepository(ReportMemberModel::class);
        // first try to find an existing member
        $reportMember = $repo->find($member->getLidnr());

        if (null === $reportMember) {
            $reportMember = new ReportMemberModel();
        }

        $reportMember->setLidnr($member->getLidnr());
        $reportMember->setEmail($member->getEmail());
        $reportMember->setLastName($member->getLastName());
        $reportMember->setMiddleName($member->getMiddleName());
        $reportMember->setInitials($member->getInitials());
        $reportMember->setFirstName($member->getFirstName());
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
        /** @var DatabaseAddressModel $address */
        foreach ($member->getAddresses() as $address) {
            $this->generateAddress($address, $reportMember);
        }

        // process mailing lists
        $this->generateLists($member, $reportMember);
        $this->emReport->persist($reportMember);
    }

    public function generateLists(
        DatabaseMemberModel $member,
        ReportMemberModel $reportMember,
    ) {
        $reportListRepo = $this->emReport->getRepository(ReportMailingListModel::class);

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

    public function generateAddress(
        DatabaseAddressModel $address,
        ?ReportMemberModel $reportMember = null,
    ) {
        $addrRepo = $this->emReport->getRepository(ReportAddressModel::class);

        if ($reportMember === null) {
            $reportMember = $this->emReport->getRepository(ReportMemberModel::class)->find($address->getMember()->getLidnr());
            if ($reportMember === null) {
                throw new LogicException('Address without member');
            }
        }

        $reportAddress = $addrRepo->find([
            'member' => $reportMember->getLidnr(),
            'type' => $address->getType(),
        ]);

        if (null === $reportAddress) {
            $reportAddress = new ReportAddressModel();
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

    public function deleteMember(DatabaseMemberModel $member)
    {
        $repo = $this->emReport->getRepository(ReportMemberModel::class);
        // first try to find an existing member
        $reportMember = $repo->find($member->getLidnr());
        $this->emReport->remove($reportMember);
    }

    public function deleteAddress(DatabaseAddressModel $address)
    {
        $repo = $this->emReport->getRepository(ReportAddressModel::class);

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
