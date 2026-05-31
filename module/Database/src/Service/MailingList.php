<?php

declare(strict_types=1);

namespace Database\Service;

use Database\Form\DeleteList as DeleteListForm;
use Database\Form\MailingList as MailingListForm;
use Database\Mapper\MailingList as MailingListMapper;
use Database\Mapper\MailingListMember as MailingListMemberMapper;
use Database\Model\MailingList as MailingListModel;
use Database\Service\Listmonk as ListmonkService;
use Database\Service\Mailman as MailmanService;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

use function boolval;
use function sprintf;

class MailingList
{
    public function __construct(
        private readonly DeleteListForm $deleteListForm,
        private readonly MailingListForm $mailingListForm,
        private readonly MailingListMapper $mailingListMapper,
        private readonly MailingListMemberMapper $mailingListMemberMapper,
        private readonly ListmonkService $listmonkService,
        private readonly MailmanService $mailmanService,
    ) {
    }

    /**
     * Get all lists.
     *
     * @return MailingListModel[]
     */
    public function getAllLists(): array
    {
        return $this->getListMapper()->findAll();
    }

    /**
     * Get a list.
     */
    public function getList(string $name): ?MailingListModel
    {
        return $this->getListMapper()->find($name);
    }

    /**
     * Add a list.
     */
    public function addList(MailingListModel $list): void
    {
        $this->getListMapper()->persist($list);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function editList(
        MailingListModel $list,
        array $data,
    ): MailingListModel {
        $list->setName($data['name']);
        $list->setEnDescription($data['en_description']);
        $list->setNlDescription($data['nl_description']);
        $list->setOnForm(boolval($data['onForm']));
        $list->setDefaultSub(boolval($data['defaultSub']));

        // If a new mailman is being set, mark all current members for creation
        $newMailman = $data['mailmanList']
            ? $this->getMailmanService()->getMailingList($data['mailmanList'])
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
            ? $this->getListmonkService()->getMailingList((int) $data['listmonkList'])
            : null;

        if (
            $newListmonk && (null === $list->getListmonkList() ||
                $list->getListmonkList()->getListmonkId() !== $newListmonk->getListmonkId())
        ) {
            $this->markAllMembersForCreation($list);
        }

        $list->setListmonkList($newListmonk);

        $this->getListMapper()->persist($list);

        return $list;
    }

    /**
     * Delete a list.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function delete(
        string $name,
        array $data,
    ): bool {
        $form = $this->getDeleteListForm();

        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $list = $this->getList($name);
        $this->getListMapper()->remove($list);

        return true;
    }

    /**
     * Mark all members of a mailing list as needing to be created on the external service.
     */
    public function markAllMembersForCreation(MailingListModel $list): void
    {
        $this->getMailingListMemberMapper()->markAllMembersForCreation($list);
    }

    /**
     * Get the delete list form.
     */
    public function getDeleteListForm(): DeleteListForm
    {
        return $this->deleteListForm;
    }

    /**
     * Get the list form.
     */
    public function getListForm(): MailingListForm
    {
        return $this->mailingListForm;
    }

    /**
     * Get the list mapper.
     */
    public function getListMapper(): MailingListMapper
    {
        return $this->mailingListMapper;
    }

    /**
     * Get the mailing list member mapper.
     */
    protected function getMailingListMemberMapper(): MailingListMemberMapper
    {
        return $this->mailingListMemberMapper;
    }

    public function getMailmanService(): MailmanService
    {
        return $this->mailmanService;
    }

    public function getListmonkService(): ListmonkService
    {
        return $this->listmonkService;
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
        $expiredMemberships = $this->getMailingListMemberMapper()->findAllExpiredOrHidden();

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
            $this->getMailingListMemberMapper()->persist($expiredMembership);
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

        $memberships = $this->getMailingListMemberMapper()->findAllPendingLocalOnly();

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
                    $this->getMailingListMemberMapper()->remove($mailingListMember);
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
                    $this->getMailingListMemberMapper()->persist($mailingListMember);
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
                'creations' => $this->getMailingListMemberMapper()->countPendingCreation(),
                'deletions' => $this->getMailingListMemberMapper()->countPendingDeletion(),
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
        return $this->getListmonkService()->isSyncLocked() || $this->getMailmanService()->isSyncLocked();
    }
}
