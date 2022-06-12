<?php

namespace Report\Service;

use Application\Service\AbstractService;
use Report\Model\MailingList as ReportList;

class Misc extends AbstractService
{
    /**
     * Export misc info.
     */
    public function generate()
    {
        // mailing lists
        $listMapper = $this->getServiceManager()->get('database_mapper_mailinglist');
        $em = $this->getServiceManager()->get('doctrine.entitymanager.orm_report');


        foreach ($listMapper->findAll() as $list) {
            $this->generateList($list);
        }
        $em->flush();
    }

    public function generateList($list)
    {
        $em = $this->getServiceManager()->get('doctrine.entitymanager.orm_report');
        $repo = $em->getRepository('Report\Model\MailingList');
        $memberRepo = $em->getRepository('Report\Model\Member');
        $reportList = $repo->find($list->getName());

        if (null === $reportList) {
            $reportList = new ReportList();
            $reportList->setName($list->getName());
        }

        $reportList->setEnDescription($list->getEnDescription());
        $reportList->setNlDescription($list->getNlDescription());
        $reportList->setOnForm($list->getOnForm());
        $reportList->setDefaultSub($list->getDefaultSub());

        $em->persist($reportList);
    }
    /**
     * Get the console object.
     */
    public function getConsole()
    {
        return $this->getServiceManager()->get('console');
    }
}
