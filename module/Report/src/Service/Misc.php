<?php

namespace Report\Service;

use Database\Mapper\MailingList as MailingListMapper;
use Doctrine\ORM\EntityManager;
use Report\Model\MailingList as ReportList;

class Misc
{
    /** @var MailingListMapper $mailingListMapper */
    private $mailingListMapper;

    /** @var EntityManager $emReport */
    private $emReport;

    /**
     * @param MailingListMapper $mailingListMapper
     * @param EntityManager $emReport
     */
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
        foreach ($this->mailingListMapper->findAll() as $list) {
            $this->generateList($list);
        }

        $this->emReport->flush();
    }

    public function generateList($list)
    {
        $repo = $this->emReport->getRepository('Report\Model\MailingList');
        $reportList = $repo->find($list->getName());

        if (null === $reportList) {
            $reportList = new ReportList();
            $reportList->setName($list->getName());
        }

        $reportList->setEnDescription($list->getEnDescription());
        $reportList->setNlDescription($list->getNlDescription());
        $reportList->setOnForm($list->getOnForm());
        $reportList->setDefaultSub($list->getDefaultSub());

        $this->emReport->persist($reportList);
    }
}
