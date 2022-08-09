<?php

namespace Database\Controller;

use Application\Model\Enums\AddressTypes;
use Database\Service\Member as MemberService;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\{
    ViewModel,
    JsonModel,
};

class MemberController extends AbstractActionController
{
    public function __construct(private readonly MemberService $memberService)
    {
    }

    /**
     * Index action.
     */
    public function indexAction(): ViewModel
    {
        return new ViewModel([]);
    }

    /**
     * Subscribe action.
     */
    public function subscribeAction(): ViewModel
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $member = $this->memberService->subscribe($request->getPost()->toArray());

            if (null !== $member) {
                $this->memberService->sendMemberSubscriptionEmail($member);

                return new ViewModel([
                    'member' => $member,
                ]);
            }
        }

        return new ViewModel([
            'form' => $this->memberService->getMemberForm(),
        ]);
    }

    /**
     * Search action.
     *
     * Searches for members.
     */
    public function searchAction(): JsonModel
    {
        $query = $this->params()->fromQuery('q');
        $res = $this->memberService->search($query);

        $res = array_map(function ($member) {
            return $member->toArray();
        }, $res);

        return new JsonModel([
            'json' => $res,
        ]);
    }

    /**
     * Show action.
     *
     * Shows member information.
     */
    public function showAction(): ViewModel
    {
        return new ViewModel($this->memberService->getMember((int) $this->params()->fromRoute('id')));
    }

    /**
     * Print action.
     *
     * Prints member information.
     */
    public function printAction(): ViewModel
    {
        return new ViewModel($this->memberService->getMember((int) $this->params()->fromRoute('id')));
    }

    /**
     * Toggle supremum action.
     *
     * Toggles if a member wants a supremum
     */
    public function setSupremumAction(): Response
    {
        $this->memberService->setSupremum(
            (int) $this->params()->fromRoute('id'),
            $this->params()->fromRoute('value'),
        );

        return $this->redirect()->toRoute('member/show', [
            'id' => $this->params()->fromRoute('id'),
        ]);
    }

    /**
     * Edit action.
     *
     * Edit member information.
     */
    public function editAction(): ViewModel
    {
        $lidnr = (int) $this->params()->fromRoute('id');

        if ($this->getRequest()->isPost()) {
            $member = $this->memberService->edit(
                $this->getRequest()->getPost()->toArray(),
                $lidnr,
            );

            if (null !== $member) {
                return new ViewModel([
                    'success' => true,
                    'member' => $member,
                ]);
            }
        }

        return new ViewModel($this->memberService->getMemberEditForm($lidnr));
    }

    /**
     * Delete action.
     *
     * Delete a member.
     */
    public function deleteAction(): ViewModel
    {
        $lidnr = (int) $this->params()->fromRoute('id');
        $member = $this->memberService->getMember($lidnr);
        $member = $member['member'];

        if ($member === null) {
            return $this->notFoundAction();
        }

        if ($this->getRequest()->isPost()) {
            $this->memberService->remove($member);

            return new ViewModel([
                'success' => true,
            ]);
        }

        return new ViewModel([
            'success' => false,
            'member' => $member,
            'canRemove' => $this->memberService->canRemove($member),
        ]);
    }

    /**
     * Lists action.
     *
     * Update list membership.
     */
    public function listsAction(): ViewModel
    {
        $lidnr = (int) $this->params()->fromRoute('id');

        if ($this->getRequest()->isPost()) {
            $member = $this->memberService->subscribeLists(
                $this->getRequest()->getPost()->toArray(),
                $lidnr,
            );

            if (null !== $member) {
                return new ViewModel([
                    'success' => true,
                    'member' => $member,
                ]);
            }
        }

        return new ViewModel($this->memberService->getListForm($lidnr));
    }

    /**
     * Membership action.
     *
     * Update / renew membership.
     */
    public function membershipAction(): ViewModel
    {
        $lidnr = (int) $this->params()->fromRoute('id');

        if ($this->getRequest()->isPost()) {
            $member = $this->memberService->membership(
                $this->getRequest()->getPost()->toArray(),
                $lidnr,
            );

            if (null !== $member) {
                return new ViewModel([
                    'success' => true,
                    'member' => $member,
                ]);
            }
        }

        return new ViewModel($this->memberService->getMemberTypeForm($lidnr));
    }

    /**
     * Expiration action.
     *
     * Extend the duration of the membership.
     */
    public function expirationAction(): ViewModel
    {
        $lidnr = (int) $this->params()->fromRoute('id');

        if ($this->getRequest()->isPost()) {
            $member = $this->memberService->expiration(
                $this->getRequest()->getPost()->toArray(),
                $lidnr,
            );

            if (null !== $member) {
                return new ViewModel([
                    'success' => true,
                    'member' => $member,
                ]);
            }
        }

        return new ViewModel($this->memberService->getMemberExpirationForm($lidnr));
    }

    /**
     * Edit address action.
     *
     * Edit a member's address.
     */
    public function editAddressAction(): ViewModel
    {
        $lidnr = (int) $this->params()->fromRoute('id');
        $type = AddressTypes::from($this->params()->fromRoute('type'));

        if ($this->getRequest()->isPost()) {
            $address = $this->memberService->editAddress(
                $this->getRequest()->getPost()->toArray(),
                $lidnr,
                $type,
            );

            if (null !== $address) {
                return new ViewModel([
                    'success' => true,
                    'address' => $address,
                ]);
            }
        }

        return new ViewModel($this->memberService->getAddressForm($lidnr, $type));
    }

    /**
     * Add address action.
     *
     * Add a member's address.
     */
    public function addAddressAction(): ViewModel
    {
        $lidnr = $this->params()->fromRoute('id');
        $type = AddressTypes::from($this->params()->fromRoute('type'));

        if ($this->getRequest()->isPost()) {
            $address = $this->memberService->addAddress(
                $this->getRequest()->getPost()->toArray(),
                $lidnr,
                $type,
            );

            if (null !== $address) {
                $vm = new ViewModel([
                    'success' => true,
                    'add' => true,
                    'address' => $address,
                ]);

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
    public function removeAddressAction(): ViewModel
    {
        $lidnr = (int) $this->params()->fromRoute('id');
        $type = AddressTypes::from($this->params()->fromRoute('type'));

        if ($this->getRequest()->isPost()) {
            $member = $this->memberService->removeAddress(
                $this->getRequest()->getPost()->toArray(),
                $lidnr,
                $type,
            );

            if (null !== $member) {
                return new ViewModel([
                    'success' => true,
                    'member' => $member,
                ]);
            }
        }

        return new ViewModel($this->memberService->getDeleteAddressForm($lidnr, $type));
    }
}
