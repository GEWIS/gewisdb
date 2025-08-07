<?php

declare(strict_types=1);

namespace Database\Service;

use Database\Form\DeleteList as DeleteListForm;
use Database\Form\MailingList as MailingListForm;
use Database\Mapper\MailingList as MailingListMapper;
use Database\Mapper\MailingListMember as MailingListMemberMapper;
use Database\Model\MailingList as MailingListModel;
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
        $list->setMailmanList($this->getMailmanService()->getMailingList($data['mailmanList']));

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
}
