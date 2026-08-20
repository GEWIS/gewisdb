<?php

declare(strict_types=1);

namespace App\Service\Report;

use App\Entity\Database\Address as DatabaseAddress;
use App\Entity\Database\Enums\MembershipTypes;
use App\Entity\Database\MailingListMember as DatabaseMailingListMember;
use App\Entity\Database\Member as DatabaseMember;
use App\Entity\Report\Address as ReportAddress;
use App\Entity\Report\MailingList as ReportMailingList;
use App\Entity\Report\MailingListMember as ReportMailingListMember;
use App\Entity\Report\Member as ReportMember;
use App\Repository\Database\MemberRepository;
use Closure;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use function array_diff;
use function array_filter;
use function array_map;
use function count;

class MemberService
{
    public function __construct(
        private readonly MemberRepository $memberRepository,
        #[Autowire(service: 'doctrine.orm.report_entity_manager')]
        private readonly EntityManagerInterface $emReport,
    ) {
    }

    /**
     * Export members.
     *
     * Progress is reported through the callback rather than written to the console here, so that the service stays
     * usable outside of a command.
     *
     * @param (Closure(int $current, int $total): void)|null $onProgress
     */
    public function generate(?Closure $onProgress = null): void
    {
        $memberCollection = $this->memberRepository->findAll();
        $total = count($memberCollection);

        $num = 0;
        foreach ($memberCollection as $member) {
            if (0 === $num++ % 20) {
                $this->emReport->flush();
                $this->emReport->clear();

                if (null !== $onProgress) {
                    $onProgress($num, $total);
                }
            }

            $this->generateMember($member);
        }

        $this->emReport->flush();
        $this->emReport->clear();
    }

    public function generateMember(DatabaseMember $member): void
    {
        $repo = $this->emReport->getRepository(ReportMember::class);
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
        $reportMember->setGeneration($member->getGeneration());
        $reportMember->setType($member->getCurrentOrLastMembership()?->getType() ?? MembershipTypes::Graduate);
        $reportMember->setMembershipEndsOn($member->getMembershipEndsOn());
        $reportMember->setExpiration($member->getExpiration());
        $reportMember->setBirth($member->getBirth());
        $reportMember->setChangedOn($member->getChangedOn());
        $reportMember->setSupremum($member->getSupremum());
        $reportMember->setHidden($member->getHidden());
        $reportMember->setDeleted($member->getDeleted());
        $reportMember->setAuthenticationKey($member->getAuthenticationKey());

        // go through addresses
        foreach ($member->getAddresses() as $address) {
            $this->generateAddress(
                $address,
                $reportMember,
            );
        }

        // process mailing lists
        $this->generateLists(
            $member,
            $reportMember,
        );
        $this->emReport->persist($reportMember);
    }

    public function generateLists(
        DatabaseMember $member,
        ReportMember $reportMember,
    ): void {
        $reportListRepo = $this->emReport->getRepository(ReportMailingList::class);

        $reportLists = array_map(
            static function ($list) {
                return $list->getMailingList()->getName();
            },
            $reportMember->getMailingListMemberships()->toArray(),
        );
        $lists = array_map(
            static function ($list) {
                return $list->getMailingList()->getName();
            },
            array_filter(
                $member->getMailingListMemberships()->toArray(),
                static function (DatabaseMailingListMember $list) use ($reportMember) {
                    return !$list->isToBeDeleted() && $list->getEmail() === $reportMember->getEmail();
                },
            ),
        );

        foreach (
            array_diff(
                $lists,
                $reportLists,
            ) as $list
        ) {
            $reportList = $reportListRepo->find($list);

            if (null === $reportList) {
                throw new LogicException('mailing list missing from reportdb');
            }

            $reportMailingListMember = new ReportMailingListMember();
            $reportMailingListMember->setMailingList($reportList);
            $reportMailingListMember->setEmail($reportMember->getEmail());

            $reportMember->addList($reportMailingListMember);
            $this->emReport->persist($reportList);
        }

        foreach (
            array_diff(
                $reportLists,
                $lists,
            ) as $list
        ) {
            $reportList = $reportListRepo->find($list);

            if (null === $reportList) {
                throw new LogicException('mailing list missing from reportdb');
            }

            foreach ($reportMember->getMailingListMemberships() as $repMLM) {
                // NOTE: $list is a mailing list name while getMailingList() is a MailingList, so this never matches
                // and a membership that disappeared from the Database is never removed from ReportDB. Ported as-is;
                // correcting it changes what a regeneration writes.
                if ($repMLM->getMailingList() !== $list) {
                    continue;
                }

                $this->emReport->remove($repMLM);
            }
        }
    }

    public function generateAddress(
        DatabaseAddress $address,
        ?ReportMember $reportMember = null,
    ): void {
        $addrRepo = $this->emReport->getRepository(ReportAddress::class);

        if (null === $reportMember) {
            $reportMember = $this->emReport->getRepository(ReportMember::class)
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

    public function deleteMember(DatabaseMember $member): void
    {
        $repo = $this->emReport->getRepository(ReportMember::class);
        // first try to find an existing member
        $reportMember = $repo->find($member->getLidnr());
        $this->emReport->remove($reportMember);
    }

    public function deleteAddress(DatabaseAddress $address): void
    {
        $repo = $this->emReport->getRepository(ReportAddress::class);

        // first try to find an existing member
        $reportAddress = $repo->find([
            'member' => $address->getMember()->getLidnr(),
            'type' => $address->getType(),
        ]);

        // If the report address has already been deleted, we don't need to do anything here.
        if (null === $reportAddress) {
            return;
        }

        $this->emReport->remove($reportAddress);
    }
}
