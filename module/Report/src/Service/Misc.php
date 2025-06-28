<?php

declare(strict_types=1);

namespace Report\Service;

use Database\Mapper\MailingList as MailingListMapper;
use Database\Mapper\MailingListMember as MailingListMemberMapper;
use Database\Model\MailingList as DatabaseMailingListModel;
use Database\Model\MailingListMember as DatabaseMailingListMemberModel;
use Doctrine\ORM\EntityManager;
use LogicException;
use Report\Model\MailingList as ReportMailingListModel;
use Report\Model\MailingListMember as ReportMailingListMemberModel;
use Report\Model\Member as ReportMemberModel;

class Misc
{
    public function __construct(
        private readonly MailingListMapper $mailingListMapper,
        private readonly MailingListMemberMapper $mailingListMemberMapper,
        private readonly EntityManager $emReport,
    ) {
    }

    /**
     * Export misc info.
     */
    public function generate(): void
    {
        foreach ($this->mailingListMapper->findAll() as $list) {
            $this->generateList($list);
        }

        foreach ($this->mailingListMemberMapper->findAfterSync() as $listMember) {
            $this->generateListMembership($listMember);
        }

        $this->emReport->flush();
    }

    /**
     * Generate a mailing list for usage in reportdb.
     */
    public function generateList(DatabaseMailingListModel $list): void
    {
        $repo = $this->emReport->getRepository(ReportMailingListModel::class);
        /** @var ReportMailingListModel|null $reportList */
        $reportList = $repo->find($list->getName());

        if (null === $reportList) {
            $reportList = new ReportMailingListModel();
            $reportList->setName($list->getName());
        }

        $reportList->setEnDescription($list->getEnDescription());
        $reportList->setNlDescription($list->getNlDescription());

        $this->emReport->persist($reportList);
    }

    /**
     * Delete a mailing list if it exists
     */
    public function deleteList(DatabaseMailingListModel $list): void
    {
        $repo = $this->emReport->getRepository(ReportMailingListModel::class);
        /** @var ReportMailingListModel|null $reportList */
        $reportList = $repo->find($list->getName());

        if (null === $reportList) {
            return;
        }

        $this->emReport->remove($reportList);
    }

    /**
     * Generate a list membership for usage in reportdb.
     */
    public function generateListMembership(DatabaseMailingListMemberModel $mailingListMember): void
    {
        $repo = $this->emReport->getRepository(ReportMailingListMemberModel::class);
        /** @var ReportMailingListMemberModel|null $reportListMembership */
        $reportListMembership = $repo->find([
            'mailingList' => $mailingListMember->getMailingList()->getName(),
            'member' => $mailingListMember->getMember()->getLidnr(),
            'email' => $mailingListMember->getEmail(),
        ]);

        if (null === $reportListMembership) {
            $reportList = $this->emReport->getRepository(ReportMailingListModel::class)
                ->find($mailingListMember->getMailingList()->getName());

            if (null === $reportList) {
                throw new LogicException('List membership without list');
            }

            $reportMember = $this->emReport->getRepository(ReportMemberModel::class)
                ->find($mailingListMember->getMember()->getLidnr());

            if (null === $reportMember) {
                throw new LogicException('List membership without member');
            }

            $reportListMembership = new ReportMailingListMemberModel();
            $reportListMembership->setMailingList($reportList);
            $reportListMembership->setMember($reportMember);
            $reportListMembership->setEmail($mailingListMember->getEmail());
        }

        // There is no possibility of updating an entry, all values are a key

        $this->emReport->persist($reportListMembership);
    }

    /**
     * Delete list membership if it exists (both when deleting a row and when setting toBeDeleted=true)
     */
    public function deleteListMembership(DatabaseMailingListMemberModel $mailingListMember): void
    {
        $repo = $this->emReport->getRepository(ReportMailingListMemberModel::class);
        /** @var ReportMailingListMemberModel|null $reportListMembership */
        $reportListMembership = $repo->find([
            'mailingList' => $mailingListMember->getMailingList()->getName(),
            'member' => $mailingListMember->getMember()->getLidnr(),
            'email' => $mailingListMember->getEmail(),
        ]);

        if (null === $reportListMembership) {
            return;
        }

        $this->emReport->remove($reportListMembership);
    }
}
