<?php

namespace Database\Controller;

use Application\Model\Enums\AddressTypes;
use Checker\Model\TueData;
use Checker\Service\Checker as CheckerService;
use Database\Model\Member as MemberModel;
use Database\Service\Member as MemberService;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\{
    ViewModel,
    JsonModel,
};

class MemberController extends AbstractActionController
{
    public function __construct(
        private readonly MemberService $memberService,
        private readonly CheckerService $checkerService,
    ) {
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
        $lidnr = (int) $this->params()->fromRoute('id');
        $member = $this->memberService->getMemberWithDecisions($lidnr);

        if (null === $member) {
            $member = $this->memberService->getMember($lidnr);

            if (null === $member) {
                return $this->notFoundAction();
            }
        }

        if ($member->getDeleted()) {
            return $this->memberIsDeleted($member);
        }

        return new ViewModel(['member' => $member]);
    }

    /**
     * Print action.
     *
     * Prints member information.
     */
    public function printAction(): ViewModel
    {
        $member = $this->memberService->getMember((int) $this->params()->fromRoute('id'));

        if (null === $member) {
            return $this->notFoundAction();
        }

        if ($member->getDeleted()) {
            return $this->memberIsDeleted($member);
        }

        return new ViewModel(['member' => $member]);
    }

    /**
     * Toggle supremum action.
     *
     * Toggles if a member wants a supremum
     */
    public function setSupremumAction(): Response|ViewModel
    {
        $member = $this->memberService->getMember((int) $this->params()->fromRoute('id'));

        if (null === $member) {
            return $this->notFoundAction();
        }

        if ($member->getDeleted()) {
            return $this->memberIsDeleted($member);
        }

        $this->memberService->setSupremum(
            $member,
            $this->params()->fromRoute('value'),
        );

        return $this->redirect()->toRoute('member/show', [
            'id' => $member->getLidnr(),
        ]);
    }

    /**
     * Edit action.
     *
     * Edit member information.
     */
    public function editAction(): ViewModel
    {
        $member = $this->memberService->getMember((int) $this->params()->fromRoute('id'));

        if (null === $member) {
            return $this->notFoundAction();
        }

        if ($member->getDeleted()) {
            return $this->memberIsDeleted($member);
        }

        if ($this->getRequest()->isPost()) {
            $member = $this->memberService->edit(
                $member,
                $this->getRequest()->getPost()->toArray(),
            );

            if (null !== $member) {
                return new ViewModel([
                    'success' => true,
                    'member' => $member,
                ]);
            }
        }

        return new ViewModel($this->memberService->getMemberEditForm($member));
    }

    /**
     * Delete action.
     *
     * Delete a member.
     */
    public function deleteAction(): ViewModel
    {
        $member = $this->memberService->getMember((int) $this->params()->fromRoute('id'));

        if ($member === null) {
            return $this->notFoundAction();
        }

        if ($member->getDeleted()) {
            return $this->memberIsDeleted($member);
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
        $member = $this->memberService->getMember((int) $this->params()->fromRoute('id'));

        if ($member === null) {
            return $this->notFoundAction();
        }

        if ($member->getDeleted()) {
            return $this->memberIsDeleted($member);
        }

        if ($this->getRequest()->isPost()) {
            $member = $this->memberService->subscribeLists(
                $member,
                $this->getRequest()->getPost()->toArray(),
            );

            if (null !== $member) {
                return new ViewModel([
                    'success' => true,
                    'member' => $member,
                ]);
            }
        }

        return new ViewModel($this->memberService->getListForm($member));
    }

    /**
     * Membership action.
     *
     * Update / renew membership.
     */
    public function membershipAction(): ViewModel
    {
        $member = $this->memberService->getMember((int) $this->params()->fromRoute('id'));

        if ($member === null) {
            return $this->notFoundAction();
        }

        if ($member->getDeleted()) {
            return $this->memberIsDeleted($member);
        }

        if ($this->getRequest()->isPost()) {
            $member = $this->memberService->membership(
                $member,
                $this->getRequest()->getPost()->toArray(),
            );

            if (null !== $member) {
                return new ViewModel([
                    'success' => true,
                    'member' => $member,
                ]);
            }
        }

        return new ViewModel([
            'form' => $this->memberService->getMemberTypeForm(),
            'member' => $member,
        ]);
    }

    /**
     * Expiration action.
     *
     * Extend the duration of the membership.
     */
    public function expirationAction(): ViewModel
    {
        $member = $this->memberService->getMember((int) $this->params()->fromRoute('id'));

        if ($member === null) {
            return $this->notFoundAction();
        }

        if ($member->getDeleted()) {
            return $this->memberIsDeleted($member);
        }

        if ($this->getRequest()->isPost()) {
            $member = $this->memberService->expiration(
                $member,
                $this->getRequest()->getPost()->toArray(),
            );

            if (null !== $member) {
                return new ViewModel([
                    'success' => true,
                    'member' => $member,
                ]);
            }
        }

        return new ViewModel([
            'form' => $this->memberService->getMemberExpirationForm(),
            'member' => $member,
        ]);
    }

    /**
     * Edit address action.
     *
     * Edit a member's address.
     */
    public function editAddressAction(): ViewModel
    {
        $type = AddressTypes::tryFrom($this->params()->fromRoute('type'));

        if (null === $type) {
            return $this->notFoundAction();
        }

        $member = $this->memberService->getMember((int) $this->params()->fromRoute('id'));

        if ($member === null) {
            return $this->notFoundAction();
        }

        if ($member->getDeleted()) {
            return $this->memberIsDeleted($member);
        }

        if ($this->getRequest()->isPost()) {
            $address = $this->memberService->editAddress(
                $member,
                $type,
                $this->getRequest()->getPost()->toArray(),
            );

            if (null !== $address) {
                return new ViewModel([
                    'success' => true,
                    'address' => $address,
                ]);
            }
        }

        $form = $this->memberService->getAddressForm($member, $type);

        return new ViewModel([
            'address' => $form->getObject(),
            'form' => $form,
        ]);
    }

    /**
     * Add address action.
     *
     * Add a member's address.
     */
    public function addAddressAction(): ViewModel
    {
        $type = AddressTypes::tryFrom($this->params()->fromRoute('type'));

        if (null === $type) {
            return $this->notFoundAction();
        }

        $member = $this->memberService->getMember((int) $this->params()->fromRoute('id'));

        if ($member === null) {
            return $this->notFoundAction();
        }

        if ($member->getDeleted()) {
            return $this->memberIsDeleted($member);
        }

        if ($this->getRequest()->isPost()) {
            $address = $this->memberService->addAddress(
                $member,
                $type,
                $this->getRequest()->getPost()->toArray(),
            );

            if (null !== $address) {
                $vm = new ViewModel([
                    'add' => true,
                    'address' => $address,
                    'success' => true,
                ]);
                $vm->setTemplate('database/member/edit-address');

                return $vm;
            }
        }

        $form = $this->memberService->getAddressForm($member, $type, true);

        $vm = new ViewModel([
            'add' => true,
            'address' => $form->getObject(),
            'form' => $form,
        ]);
        $vm->setTemplate('database/member/edit-address');

        return $vm;
    }

    /**
     * Remove address action.
     *
     * Remove a member's address.
     */
    public function removeAddressAction(): ViewModel
    {
        $type = AddressTypes::tryFrom($this->params()->fromRoute('type'));

        if (null === $type) {
            return $this->notFoundAction();
        }

        $member = $this->memberService->getMember((int) $this->params()->fromRoute('id'));

        if ($member === null) {
            return $this->notFoundAction();
        }

        if ($member->getDeleted()) {
            return $this->memberIsDeleted($member);
        }

        if ($this->getRequest()->isPost()) {
            $member = $this->memberService->removeAddress(
                $member,
                $type,
                $this->getRequest()->getPost()->toArray(),
            );

            if (null !== $member) {
                return new ViewModel([
                    'success' => true,
                    'member' => $member,
                ]);
            }
        }

        return new ViewModel([
            'addressType' => $type,
            'form' => $this->memberService->getDeleteAddressForm(),
            'member' => $member,
        ]);
    }

    /**
     * Lookup TUe data action
     *
     * Gets the TUe data (optionally with a user ID provided)
     */
    public function tueLookupAction(): ViewModel
    {
        $username = $this->params()->fromQuery('u');

        return new ViewModel([
            'username' => $username,
        ]);
    }

    /**
     * Lookup TUe data action (JSON API)
     *
     * Returns the TUe data for a given user id
     */
    public function tueRequestAction(): JsonModel
    {
        $username = $this->params()->fromQuery('u');
        $data = $this->checkerService->tueDataObject();
        $data->setUser($username);

        return new JsonModel(
            $data->toArray(),
        );
    }

    private function memberIsDeleted(MemberModel $member): ViewModel
    {
        $viewModel = new ViewModel([
            'member' => $member,
        ]);
        $viewModel->setTemplate('database/member/deleted.phtml');

        return $viewModel;
    }
}
