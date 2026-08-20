<?php

declare(strict_types=1);

namespace App\Service\Database;

use App\Entity\Database\ListmonkMailingList;
use App\Entity\Database\MailingList;
use App\Entity\Database\MailmanMailingList;
use App\Repository\Database\MailingListMemberRepository;
use App\Repository\Database\MailingListRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

use function array_filter;
use function array_values;
use function sprintf;

class MailingListService
{
    public function __construct(
        private readonly MailingListRepository $mailingListRepository,
        private readonly MailingListMemberRepository $mailingListMemberRepository,
        private readonly ListmonkService $listmonkService,
        private readonly MailmanService $mailmanService,
        private readonly EntityManagerInterface $entityManager,
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
     * Store the changes made to an existing list.
     */
    public function editList(MailingList $list): void
    {
        // Where a list's members have to exist is decided by the external list it is bound to, so binding it to
        // another one means every current member still has to be created there. The previous binding is read from
        // the identity map because the new one has already been written onto the entity by the time this runs.
        $original = $this->entityManager->getUnitOfWork()->getOriginalEntityData($list);

        if (
            (
                $list->hasMailmanList()
                && $list->getMailmanList() !== ($original['mailmanList'] ?? null)
            )
            || (
                $list->hasListmonkList()
                && $list->getListmonkList() !== ($original['listmonkList'] ?? null)
            )
        ) {
            $this->markAllMembersForCreation($list);
        }

        $this->mailingListRepository->persist($list);
    }

    /**
     * Delete a list.
     */
    public function delete(MailingList $list): void
    {
        $this->mailingListRepository->remove($list);
    }

    /**
     * The Mailman lists that may be bound to a mailing list.
     *
     * A Mailman list belongs to at most one mailing list, so one that is already taken is not offered again. The
     * list being edited is the exception: saving it again with the binding it already has must remain possible.
     *
     * @return MailmanMailingList[]
     */
    public function getSelectableMailmanLists(?MailingList $list = null): array
    {
        return array_values(array_filter(
            $this->mailmanService->getMailingLists(),
            static function (MailmanMailingList $mailmanList) use ($list): bool {
                return !$mailmanList->isManaged()
                    || $mailmanList->getMailingList()?->getName() === $list?->getName();
            },
        ));
    }

    /**
     * The Listmonk lists that may be bound to a mailing list.
     *
     * @return ListmonkMailingList[]
     */
    public function getSelectableListmonkLists(?MailingList $list = null): array
    {
        return array_values(array_filter(
            $this->listmonkService->getMailingLists(),
            static function (ListmonkMailingList $listmonkList) use ($list): bool {
                return !$listmonkList->isManaged()
                    || $listmonkList->getMailingList()?->getName() === $list?->getName();
            },
        ));
    }

    /**
     * The moment the Listmonk lists were last fetched.
     */
    public function getListmonkLastFetch(): ?DateTime
    {
        return $this->listmonkService->getLastFetchTime();
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
            if (
                !$member->getHidden()
                && $member->hasActiveRenewalLink()
            ) {
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
