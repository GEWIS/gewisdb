<?php

namespace Database\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

use Database\Model\Member;

class MemberController extends AbstractActionController
{

    /**
     * Index action.
     */
    public function indexAction()
    {
        return new ViewModel(array());
    }

    /**
     * Subscribe action.
     */
    public function subscribeAction()
    {
        $request = $this->getRequest();
        $service = $this->getMemberService();

        if ($request->isPost()) {
            $member = $service->subscribe($request->getPost());

            if (null !== $member) {
                return new ViewModel(array(
                    'member' => $member
                ));
            }
        }

        return new ViewModel(array(
            'form' => $this->getMemberService()->getMemberForm()
        ));
    }

    /**
     * Search action.
     *
     * Searches for members.
     */
    public function searchAction()
    {
        $service = $this->getMemberService();

        $query = $this->params()->fromQuery('q');

        $res = $service->search($query);

        $res = array_map(function ($member) {
            return $member->toArray();
        }, $res);

        return new JsonModel(array(
            'json' => $res
        ));
    }

    /**
     * Show action.
     *
     * Shows member information.
     */
    public function showAction()
    {
        $service = $this->getMemberService();

        return new ViewModel($service->getMember($this->params()->fromRoute('id')));
    }

    /**
     * Print action.
     *
     * Prints member information.
     */
    public function printAction()
    {
        $service = $this->getMemberService();

        return new ViewModel($service->getMember($this->params()->fromRoute('id')));
    }

    /**
     * Toggle supremum action.
     *
     * Toggles if a member wants a supremum
     */
    public function setSupremumAction()
    {
        $service = $this->getMemberService();

        $service->setSupremum($this->params()->fromRoute('id'), $this->params()->fromRoute('value'));

        return $this->redirect()->toRoute('member/show', [
            'id' => $this->params()->fromRoute('id')
        ]);
    }

    /**
     * Edit action.
     *
     * Edit member information.
     */
    public function editAction()
    {
        $service = $this->getMemberService();

        $lidnr = $this->params()->fromRoute('id');

        if ($this->getRequest()->isPost()) {
            $member = $service->edit($this->getRequest()->getPost(), $lidnr);
            if (null !== $member) {
                return new ViewModel(array(
                    'success' => true,
                    'member' => $member
                ));
            }
        }

        return new ViewModel($service->getMemberEditForm($lidnr));
    }

    /**
     * Lists action.
     *
     * Update list membership.
     */
    public function listsAction()
    {
        $service = $this->getMemberService();

        $lidnr = $this->params()->fromRoute('id');

        if ($this->getRequest()->isPost()) {
            $member = $service->subscribeLists($this->getRequest()->getPost(), $lidnr);
            if (null !== $member) {
                return new ViewModel(array(
                    'success' => true,
                    'member' => $member
                ));
            }
        }

        return new ViewModel($service->getListForm($lidnr));
    }

    /**
     * Membership action.
     *
     * Update / renew membership.
     */
    public function membershipAction()
    {
        $service = $this->getMemberService();

        $lidnr = $this->params()->fromRoute('id');

        if ($this->getRequest()->isPost()) {
            $member = $service->membership($this->getRequest()->getPost(), $lidnr);
            if (null !== $member) {
                return new ViewModel(array(
                    'success' => true,
                    'member' => $member
                ));
            }
        }

        return new ViewModel($service->getMemberTypeForm($lidnr));
    }

    /**
     * Edit address action.
     *
     * Edit a member's address.
     */
    public function editAddressAction()
    {
        $service = $this->getMemberService();

        $lidnr = $this->params()->fromRoute('id');
        $type = $this->params()->fromRoute('type');

        if ($this->getRequest()->isPost()) {
            $address = $service->editAddress($this->getRequest()->getPost(), $lidnr, $type);
            if (null !== $address) {
                return new ViewModel(array(
                    'success' => true,
                    'address' => $address
                ));
            }
        }

        return new ViewModel($service->getAddressForm($lidnr, $type));
    }

    /**
     * Add address action.
     *
     * Add a member's address.
     */
    public function addAddressAction()
    {
        $service = $this->getMemberService();

        $lidnr = $this->params()->fromRoute('id');
        $type = $this->params()->fromRoute('type');

        if ($this->getRequest()->isPost()) {
            $address = $service->addAddress($this->getRequest()->getPost(), $lidnr, $type);
            if (null !== $address) {
                $vm = new ViewModel(array(
                    'success' => true,
                    'add' => true,
                    'address' => $address
                ));
                $vm->setTemplate('database/member/edit-address');
                return $vm;
            }
        }

        $vm = new ViewModel($service->getAddressForm($lidnr, $type, true));
        $vm->setTemplate('database/member/edit-address');
        $vm->add = true;
        return $vm;
    }

    /**
     * Remove address action.
     *
     * Remove a member's address.
     */
    public function removeAddressAction()
    {
        $service = $this->getMemberService();

        $lidnr = $this->params()->fromRoute('id');
        $type = $this->params()->fromRoute('type');

        if ($this->getRequest()->isPost()) {
            $member = $service->removeAddress($this->getRequest()->getPost(), $lidnr, $type);
            if (null !== $member) {
                return new ViewModel(array(
                    'success' => true,
                    'member' => $member
                ));
            }
        }

        return new ViewModel($service->getDeleteAddressForm($lidnr, $type));
    }

    /**
     * Get the member service.
     *
     * @return \Database\Service\Member
     */
    public function getMemberService()
    {
        return $this->getServiceLocator()->get('database_service_member');
    }
}
