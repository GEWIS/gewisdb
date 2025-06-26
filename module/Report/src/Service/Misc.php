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

        foreach ($this->mailingListMemberMapper->findAll() as $listMember) {
            $this->generateListMembership($listMember);
        }

        $this->emReport->flush();
    }

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

    public function generateListMembership(DatabaseMailingListMemberModel $mailingListMember): void
    {
        $repo = $this->emReport->getRepository(ReportMailingListMemberModel::class);
        /** @var ReportMailingListMemberModel|null $reportListMembership */
        $reportListMembership = $repo->find([
            'mailingList' => $mailingListMember->getMailingList()->getName(),
            'member' => $mailingListMember->getMember()->getLidnr(),
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
        }

        $reportListMembership->setLastSyncOn($mailingListMember->getLastSyncOn());
        $reportListMembership->setLastSyncSuccess($mailingListMember->isLastSyncSuccess());
        $reportListMembership->setToBeDeleted($mailingListMember->isToBeDeleted());

        $this->emReport->persist($reportListMembership);
    }
}
