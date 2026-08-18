<?php

declare(strict_types=1);

namespace App\Service\Mailing;

use App\Entity\Mailing\MailingList;
use App\Repository\Mailing\MailingListMemberRepository;
use App\Repository\Mailing\MailingListRepository;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

use function boolval;
use function sprintf;

class MailingListService
{
    public function __construct(
        private readonly MailingListRepository $mailingListRepository,
        private readonly MailingListMemberRepository $mailingListMemberRepository,
        private readonly ListmonkService $listmonkService,
        private readonly MailmanService $mailmanService,
    ) {
    }

    /**
     * Get all lists.
     *
     * @return MailingList[]
     */
    public function getAllLists(): array
    {
        return $this->mailingListRepository->findAll();
    }

    /**
     * Get a list.
     */
    public function getList(string $name): ?MailingList
    {
        return $this->mailingListRepository->find($name);
    }

    /**
     * Add a list.
     */
    public function addList(MailingList $list): void
    {
        $this->mailingListRepository->persist($list);
    }

    /**
     * @param array<string, mixed> $data
     * @phpstan-param array{
     *     name: string,
     *     en_description: string,
     *     nl_description: string,
     *     onForm: mixed,
     *     defaultSub: mixed,
     *     mailmanList: ?string,
     *     listmonkList: int|string|null,
     * } $data
     */
    public function editList(
        MailingList $list,
        array $data,
    ): MailingList {
        $list->setName($data['name']);
        $list->setEnDescription($data['en_description']);
        $list->setNlDescription($data['nl_description']);
        $list->setOnForm(boolval($data['onForm']));
        $list->setDefaultSub(boolval($data['defaultSub']));

        // If a new mailman is being set, mark all current members for creation
        $newMailman = $data['mailmanList']
            ? $this->mailmanService->getMailingList($data['mailmanList'])
            : null;

        if (
            $newMailman && (null === $list->getMailmanList() ||
                $list->getMailmanList()->getMailmanId() !== $newMailman->getMailmanId())
        ) {
            $this->markAllMembersForCreation($list);
        }

        $list->setMailmanList($newMailman);

        // If a new listmonk is being set, mark all current members for creation
        $newListmonk = $data['listmonkList']
            ? $this->listmonkService->getMailingList((int) $data['listmonkList'])
            : null;

        if (
            $newListmonk && (null === $list->getListmonkList() ||
                $list->getListmonkList()->getListmonkId() !== $newListmonk->getListmonkId())
        ) {
            $this->markAllMembersForCreation($list);
        }

        $list->setListmonkList($newListmonk);

        $this->mailingListRepository->persist($list);

        return $list;
    }

    /**
     * Delete a list.
     */
    public function delete(string $name): void
    {
        $list = $this->getList($name);

        $this->mailingListRepository->remove($list);
    }

    /**
     * Mark all members of a mailing list as needing to be created on the external service.
     */
    public function markAllMembersForCreation(MailingList $list): void
    {
        $this->mailingListMemberRepository->markAllMembersForCreation($list);
    }

    /**
     * Perform maintenance to abnormal mailing list situations
     * This does not directly operate on mailman
     */
    public function performMaintenance(
        OutputInterface $output = new NullOutput(),
        bool $dryRun = false,
    ): void {
        $output->writeln('Checking for mailing list memberships for expired/hidden members:');
        $expiredMemberships = $this->mailingListMemberRepository->findAllExpiredOrHidden();

        foreach ($expiredMemberships as $expiredMembership) {
            $member = $expiredMembership->getMember();

            // If the member still is able to renew, do not delete memberships yet
            if (!$member->getHidden() && $member->hasActiveRenewalLink()) {
                continue;
            }

            $output->writeln(
                sprintf(
                    '-> Scheduling deletion of mailing list membership for %s on %s',
                    $expiredMembership->getEmail(),
                    $expiredMembership->getMailingList()->getName(),
                ),
                OutputInterface::VERBOSITY_VERBOSE,
            );

            if ($dryRun) {
                continue;
            }

            // Else, schedule deletion
            $expiredMembership->setToBeDeleted(true);
            $this->mailingListMemberRepository->persist($expiredMembership);
        }
    }

    /**
     * Process pending local-only mailing list memberships.
     *
     * For lists without a Mailman or Listmonk binding, external sync is impossible, so
     * pending creations are marked successful and pending deletions are removed.
     */
    public function syncLocalOnlyMembership(
        OutputInterface $output = new NullOutput(),
        bool $dryRun = false,
    ): void {
        $output->writeln('Processing pending memberships for local-only mailing lists:');

        $memberships = $this->mailingListMemberRepository->findAllPendingLocalOnly();

        foreach ($memberships as $mailingListMember) {
            $listName = $mailingListMember->getMailingList()->getName();
            $email = $mailingListMember->getEmail();

            if ($mailingListMember->isToBeDeleted()) {
                $output->writeln(
                    sprintf(
                        '-> Removing local-only mailing list membership for %s on %s',
                        $email,
                        $listName,
                    ),
                    OutputInterface::VERBOSITY_VERBOSE,
                );

                if (!$dryRun) {
                    $this->mailingListMemberRepository->remove($mailingListMember);
                }
            }

            if ($mailingListMember->isToBeCreated()) {
                $output->writeln(
                    sprintf(
                        '-> Clearing pending creation for local-only mailing list membership %s on %s',
                        $email,
                        $listName,
                    ),
                    OutputInterface::VERBOSITY_VERBOSE,
                );

                if (!$dryRun) {
                    $mailingListMember->setToBeCreated(false);
                    $this->mailingListMemberRepository->persist($mailingListMember);
                }
            }

            if ($dryRun) {
                continue;
            }

            $mailingListMember->setLastSyncOn();
            $mailingListMember->setLastSyncSuccess(true);
        }
    }

    /**
     * @return array{
     *     mailingListChangesPending: array{
     *       creations: int,
     *       deletions: int,
     *     },
     * }
     */
    public function getFrontpageData(): array
    {
        return [
            'mailingListChangesPending' => [
                'creations' => $this->mailingListMemberRepository->countPendingCreation(),
                'deletions' => $this->mailingListMemberRepository->countPendingDeletion(),
            ],
        ];
    }

    /**
     * Checks whether any of the mailing list syncs are locked
     *
     * @return bool sync locked
     */
    public function isSyncLocked(): bool
    {
        return $this->listmonkService->isSyncLocked() || $this->mailmanService->isSyncLocked();
    }
}
