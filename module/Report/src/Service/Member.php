<?php

declare(strict_types=1);

namespace Report\Service;

use Database\Mapper\Member as MemberMapper;
use Database\Model\Address as DatabaseAddressModel;
use Database\Model\Member as DatabaseMemberModel;
use Doctrine\ORM\EntityManager;
use Laminas\ProgressBar\Adapter\Console;
use Laminas\ProgressBar\ProgressBar;
use LogicException;
use Report\Model\Address as ReportAddressModel;
use Report\Model\MailingList as ReportMailingListModel;
use Report\Model\Member as ReportMemberModel;

use function array_diff;
use function array_map;
use function count;

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
    public function generate(): void
    {
        $memberCollection = $this->memberMapper->findAll();

        $adapter = new Console();
        $progress = new ProgressBar($adapter, 0, count($memberCollection));

        $num = 0;
        /** @var DatabaseMemberModel $member */
        foreach ($memberCollection as $member) {
            if (0 === $num++ % 20) {
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

    public function generateMember(DatabaseMemberModel $member): void
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
        $reportMember->setHidden($member->getHidden());
        $reportMember->setDeleted($member->getDeleted());
        $reportMember->setAuthenticationKey($member->getAuthenticationKey());

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
    ): void {
        $reportListRepo = $this->emReport->getRepository(ReportMailingListModel::class);

        $reportLists = array_map(static function ($list) {
            return $list->getName();
        }, $reportMember->getLists()->toArray());
        $lists = array_map(static function ($list) {
            return $list->getName();
        }, $member->getLists()->toArray());

        foreach (array_diff($lists, $reportLists) as $list) {
            $reportList = $reportListRepo->find($list);

            if (null === $reportList) {
                throw new LogicException('mailing list missing from reportdb');
            }

            $reportMember->addList($reportList);
            $this->emReport->persist($reportList);
        }

        foreach (array_diff($reportLists, $lists) as $list) {
            $reportList = $reportListRepo->find($list);

            if (null === $reportList) {
                throw new LogicException('mailing list missing from reportdb');
            }

            $reportMember->removeList($reportList);
            $this->emReport->persist($reportList);
        }
    }

    public function generateAddress(
        DatabaseAddressModel $address,
        ?ReportMemberModel $reportMember = null,
    ): void {
        $addrRepo = $this->emReport->getRepository(ReportAddressModel::class);

        if (null === $reportMember) {
            $reportMember = $this->emReport->getRepository(ReportMemberModel::class)
                ->find($address->getMember()->getLidnr());
            if (null === $reportMember) {
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

    public function deleteMember(DatabaseMemberModel $member): void
    {
        $repo = $this->emReport->getRepository(ReportMemberModel::class);
        // first try to find an existing member
        $reportMember = $repo->find($member->getLidnr());
        $this->emReport->remove($reportMember);
    }

    public function deleteAddress(DatabaseAddressModel $address): void
    {
        $repo = $this->emReport->getRepository(ReportAddressModel::class);

        // first try to find an existing member
        $reportAddress = $repo->find([
            'member' => $address->getMember()->getLidnr(),
            'type' => $address->getType(),
        ]);

        $this->emReport->remove($reportAddress);
    }
}
