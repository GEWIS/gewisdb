<?php

namespace Database\Service;

use Application\Service\AbstractService;
use Database\Model\MailingList as ListModel;

class MailingList extends AbstractService
{
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

        $this->getEventManager()->trigger(__FUNCTION__ . '.pre', array('list' => $list));
        $this->getListMapper()->persist($list);
        $this->getEventManager()->trigger(__FUNCTION__ . '.post', array('list' => $list));

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

        $this->getEventManager()->trigger(__FUNCTION__ . '.pre', array('list' => $list));
        $this->getListMapper()->remove($list);
        $this->getEventManager()->trigger(__FUNCTION__ . '.post', array('list' => $list));

        return true;
    }

    /**
     * Get the delete list form.
     *
     * @return \Database\Form\DeleteList
     */
    public function getDeleteListForm()
    {
        return $this->getServiceManager()->get('database_form_deletelist');
    }

    /**
     * Get the list form.
     *
     * @return \Database\Form\MailingList
     */
    public function getListForm()
    {
        return $this->getServiceManager()->get('database_form_mailinglist');
    }

    /**
     * Get the list mapper.
     *
     * @return \Database\Mapper\MailingList
     */
    public function getListMapper()
    {
        return $this->getServiceManager()->get('database_mapper_mailinglist');
    }
}
