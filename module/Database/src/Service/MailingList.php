<?php

namespace Database\Service;

use Database\Form\DeleteList as DeleteListForm;
use Database\Form\MailingList as MailingListForm;
use Database\Mapper\MailingList as MailingListMapper;
use Database\Model\MailingList as ListModel;

class MailingList
{
    /** @var DeleteListForm $deleteListForm */
    private $deleteListForm;

    /** @var MailingListForm $mailingListForm */
    private $mailingListForm;

    /** @var MailingListMapper $mailingListMapper */
    private $mailingListMapper;

    /**
     * @param DeleteListForm $deleteListForm
     * @param MailingListForm $mailingListForm
     * @param MailingListMapper $mailingListMapper
     */
    public function __construct(
        DeleteListForm $deleteListForm,
        MailingListForm $mailingListForm,
        MailingListMapper $mailingListMapper
    ) {
        $this->deleteListForm = $deleteListForm;
        $this->mailingListForm = $mailingListForm;
        $this->mailingListMapper = $mailingListMapper;
    }

    /**
     * Get all lists.
     *
     * @return array of ListModel's
     */
    public function getAllLists()
    {
        return $this->getListMapper()->findAll();
    }

    /**
     * Get a list.
     *
     * @param string $name
     *
     * @return ListModel
     */
    public function getList($name)
    {
        return $this->getListMapper()->find($name);
    }

    /**
     * Add a list.
     *
     * @param $data POST data.
     *
     * @return boolean if succeeded
     */
    public function addList($data)
    {
        $form = $this->getListForm();

        $form->setData($data);
        $form->bind(new ListModel());

        if (!$form->isValid()) {
            return false;
        }

        $list = $form->getData();
        $this->getListMapper()->persist($list);

        return true;
    }

    /**
     * Delete a list.
     *
     * @param string $name Name of the list to delete
     * @param array $data Form data
     *
     * @return boolean If deleted
     */
    public function delete($name, $data)
    {
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
     *
     * @return DeleteListForm
     */
    public function getDeleteListForm(): DeleteListForm
    {
        return $this->deleteListForm;
    }

    /**
     * Get the list form.
     *
     * @return MailingListForm
     */
    public function getListForm(): MailingListForm
    {
        return $this->mailingListForm;
    }

    /**
     * Get the list mapper.
     *
     * @return MailingListMapper
     */
    public function getListMapper(): MailingListMapper
    {
        return $this->mailingListMapper;
    }
}
