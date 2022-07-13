<?php

namespace Database\Controller;

use Database\Model\Member;
use Database\Service\Member as MemberService;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class MemberController extends AbstractActionController
{
    /** @var MemberService $memberService */
    private $memberService;

    /**
     * @param MemberService $memberService
     */
    public function __construct(MemberService $memberService)
    {
        $this->memberService = $memberService;
    }

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

        if ($request->isPost()) {
            $member = $this->memberService->subscribe($request->getPost());

            if (null !== $member) {
                $this->memberService->sendMemberSubscriptionEmail($member);
                return new ViewModel(array(
                    'member' => $member
                ));
            }
        }

        return new ViewModel(array(
            'form' => $this->memberService->getMemberForm()
        ));
    }

    /**
     * Search action.
     *
     * Searches for members.
     */
    public function searchAction()
    {
        $query = $this->params()->fromQuery('q');
        $res = $this->memberService->search($query);

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
        return new ViewModel($this->memberService->getMember($this->params()->fromRoute('id')));
    }

    /**
     * Print action.
     *
     * Prints member information.
     */
    public function printAction()
    {
        return new ViewModel($this->memberService->getMember($this->params()->fromRoute('id')));
    }

    /**
     * Toggle supremum action.
     *
     * Toggles if a member wants a supremum
     */
    public function setSupremumAction()
    {
        $this->memberService->setSupremum($this->params()->fromRoute('id'), $this->params()->fromRoute('value'));

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
        $lidnr = $this->params()->fromRoute('id');

        if ($this->getRequest()->isPost()) {
            $member = $this->memberService->edit($this->getRequest()->getPost(), $lidnr);
            if (null !== $member) {
                return new ViewModel(array(
                    'success' => true,
                    'member' => $member
                ));
            }
        }

        return new ViewModel($this->memberService->getMemberEditForm($lidnr));
    }

    /**
     * Delete action.
     *
     * Delete a member.
     */
    public function deleteAction()
    {
        $lidnr = $this->params()->fromRoute('id');
        $member = $this->memberService->getMember($lidnr);
        $member = $member['member'];

        if ($this->getRequest()->isPost()) {
            $this->memberService->remove($member);
            return new ViewModel([
                'success' => true,
            ]);
        }

        return new ViewModel([
            'success' => false,
            'member' => $member,
            'canRemove' => $this->memberService->canRemove($member)
        ]);
    }

    /**
     * Lists action.
     *
     * Update list membership.
     */
    public function listsAction()
    {
        $lidnr = $this->params()->fromRoute('id');

        if ($this->getRequest()->isPost()) {
            $member = $this->memberService->subscribeLists($this->getRequest()->getPost(), $lidnr);

            if (null !== $member) {
                return new ViewModel(array(
                    'success' => true,
                    'member' => $member
                ));
            }
        }

        return new ViewModel($this->memberService->getListForm($lidnr));
    }

    /**
     * Membership action.
     *
     * Update / renew membership.
     */
    public function membershipAction()
    {
        $lidnr = $this->params()->fromRoute('id');

        if ($this->getRequest()->isPost()) {
            $member = $this->memberService->membership($this->getRequest()->getPost(), $lidnr);

            if (null !== $member) {
                return new ViewModel(array(
                    'success' => true,
                    'member' => $member
                ));
            }
        }

        return new ViewModel($this->memberService->getMemberTypeForm($lidnr));
    }

    /**
     * Expiration action.
     *
     * Extend the duration of the membership.
     */
    public function expirationAction()
    {
        $lidnr = $this->params()->fromRoute('id');

        if ($this->getRequest()->isPost()) {
            $member = $this->memberService->expiration($this->getRequest()->getPost(), $lidnr);

            if (null !== $member) {
                return new ViewModel(array(
                    'success' => true,
                    'member' => $member
                ));
            }
        }

        return new ViewModel($this->memberService->getMemberExpirationForm($lidnr));
    }

    /**
     * Edit address action.
     *
     * Edit a member's address.
     */
    public function editAddressAction()
    {
        $lidnr = $this->params()->fromRoute('id');
        $type = $this->params()->fromRoute('type');

        if ($this->getRequest()->isPost()) {
            $address = $this->memberService->editAddress($this->getRequest()->getPost(), $lidnr, $type);

            if (null !== $address) {
                return new ViewModel(array(
                    'success' => true,
                    'address' => $address
                ));
            }
        }

        return new ViewModel($this->memberService->getAddressForm($lidnr, $type));
    }

    /**
     * Add address action.
     *
     * Add a member's address.
     */
    public function addAddressAction()
    {
        $lidnr = $this->params()->fromRoute('id');
        $type = $this->params()->fromRoute('type');

        if ($this->getRequest()->isPost()) {
            $address = $this->memberService->addAddress($this->getRequest()->getPost(), $lidnr, $type);

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

        $vm = new ViewModel($this->memberService->getAddressForm($lidnr, $type, true));
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
        $lidnr = $this->params()->fromRoute('id');
        $type = $this->params()->fromRoute('type');

        if ($this->getRequest()->isPost()) {
            $member = $this->memberService->removeAddress($this->getRequest()->getPost(), $lidnr, $type);

            if (null !== $member) {
                return new ViewModel(array(
                    'success' => true,
                    'member' => $member
                ));
            }
        }

        return new ViewModel($this->memberService->getDeleteAddressForm($lidnr, $type));
    }
}
