<?php

namespace Database\Service;

use Database\Form\DeleteList as DeleteListForm;
use Database\Form\MailingList as MailingListForm;
use Database\Mapper\MailingList as MailingListMapper;
use Database\Model\MailingList as MailingListModel;

class MailingList
{
    public function __construct(
        private readonly DeleteListForm $deleteListForm,
        private readonly MailingListForm $mailingListForm,
        private readonly MailingListMapper $mailingListMapper,
    ) {
    }

    /**
     * Get all lists.
     *
     * @return array of ListModel's
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
    public function addList(array $data): bool
    {
        $form = $this->getListForm();

        $form->bind(new MailingListModel());
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        /** @var MailingListModel $list */
        $list = $form->getData();
        $this->getListMapper()->persist($list);

        return true;
    }

    /**
     * Delete a list.
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
}
