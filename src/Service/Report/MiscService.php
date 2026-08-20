<?php

declare(strict_types=1);

namespace App\Service\Report;

use App\Entity\Database\MailingList as DatabaseMailingList;
use App\Entity\Database\MailingListMember as DatabaseMailingListMember;
use App\Entity\Report\MailingList as ReportMailingList;
use App\Entity\Report\MailingListMember as ReportMailingListMember;
use App\Entity\Report\Member as ReportMember;
use App\Repository\Database\MailingListRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MiscService
{
    public function __construct(
        private readonly MailingListRepository $mailingListRepository,
        #[Autowire(service: 'doctrine.orm.report_entity_manager')]
        private readonly EntityManagerInterface $emReport,
    ) {
    }

    /**
     * Generate the mailing lists themselves.
     *
     * The memberships of those lists are not generated here. A membership needs both its list and its member to be
     * in ReportDB already, and generating the members is what puts each of their memberships there -- so a pass over
     * the memberships either runs before the members it needs, or after they have already been written.
     */
    public function generateLists(): void
    {
        foreach ($this->mailingListRepository->findAll() as $list) {
            $this->generateList($list);
        }

        $this->emReport->flush();
    }

    /**
     * Generate a mailing list for usage in reportdb.
     */
    public function generateList(DatabaseMailingList $list): void
    {
        $repo = $this->emReport->getRepository(ReportMailingList::class);
        $reportList = $repo->find($list->getName());

        if (null === $reportList) {
            $reportList = new ReportMailingList();
            $reportList->setName($list->getName());
        }

        $reportList->setEnDescription($list->getEnDescription());
        $reportList->setNlDescription($list->getNlDescription());

        $this->emReport->persist($reportList);
    }

    /**
     * Delete a mailing list if it exists
     */
    public function deleteList(DatabaseMailingList $list): void
    {
        $repo = $this->emReport->getRepository(ReportMailingList::class);
        $reportList = $repo->find($list->getName());

        if (null === $reportList) {
            return;
        }

        $this->emReport->remove($reportList);
    }

    /**
     * Generate a list membership for usage in reportdb.
     */
    public function generateListMembership(DatabaseMailingListMember $mailingListMember): void
    {
        $repo = $this->emReport->getRepository(ReportMailingListMember::class);
        $reportListMembership = $repo->find([
            'mailingList' => $mailingListMember->getMailingList()->getName(),
            'email' => $mailingListMember->getEmail(),
        ]);

        if (null === $reportListMembership) {
            $reportList = $this->emReport->getRepository(ReportMailingList::class)
                ->find($mailingListMember->getMailingList()->getName());

            if (null === $reportList) {
                throw new LogicException('List membership without list');
            }

            $reportMember = $this->emReport->getRepository(ReportMember::class)
                ->find($mailingListMember->getMember()->getLidnr());

            if (null === $reportMember) {
                throw new LogicException('List membership without member');
            }

            $reportListMembership = new ReportMailingListMember();
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
    public function deleteListMembership(DatabaseMailingListMember $mailingListMember): void
    {
        $repo = $this->emReport->getRepository(ReportMailingListMember::class);
        $reportListMembership = $repo->find([
            'mailingList' => $mailingListMember->getMailingList()->getName(),
            'email' => $mailingListMember->getEmail(),
        ]);

        if (null === $reportListMembership) {
            return;
        }

        $this->emReport->remove($reportListMembership);
    }
}
