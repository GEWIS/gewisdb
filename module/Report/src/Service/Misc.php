<?php

namespace Report\Service;

use Database\Mapper\MailingList as MailingListMapper;
use Database\Model\MailingList as DatabaseMailingListModel;
use Doctrine\ORM\EntityManager;
use Report\Model\MailingList as ReportMailingListModel;

class Misc
{
    private MailingListMapper $mailingListMapper;

    private EntityManager $emReport;

    public function __construct(
        MailingListMapper $mailingListMapper,
        EntityManager $emReport,
    ) {
        $this->mailingListMapper = $mailingListMapper;
        $this->emReport = $emReport;
    }

    /**
     * Export misc info.
     */
    public function generate()
    {
        /** @var DatabaseMailingListModel $list */
        foreach ($this->mailingListMapper->findAll() as $list) {
            $this->generateList($list);
        }

        $this->emReport->flush();
    }

    public function generateList(DatabaseMailingListModel $list)
    {
        $repo = $this->emReport->getRepository(ReportMailingListModel::class);
        $reportList = $repo->find($list->getName());

        if (null === $reportList) {
            $reportList = new ReportMailingListModel();
            $reportList->setName($list->getName());
        }

        $reportList->setEnDescription($list->getEnDescription());
        $reportList->setNlDescription($list->getNlDescription());
        $reportList->setOnForm($list->getOnForm());
        $reportList->setDefaultSub($list->getDefaultSub());

        $this->emReport->persist($reportList);
    }
}
