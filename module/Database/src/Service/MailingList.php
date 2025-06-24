<?php

declare(strict_types=1);

namespace Database\Service;

use Database\Form\DeleteList as DeleteListForm;
use Database\Form\MailingList as MailingListForm;
use Database\Mapper\MailingList as MailingListMapper;
use Database\Model\MailingList as MailingListModel;
use Database\Service\Mailman as MailmanService;

use function boolval;

class MailingList
{
    public function __construct(
        private readonly DeleteListForm $deleteListForm,
        private readonly MailingListForm $mailingListForm,
        private readonly MailingListMapper $mailingListMapper,
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

    public function getMailmanService(): MailmanService
    {
        return $this->mailmanService;
    }
}
