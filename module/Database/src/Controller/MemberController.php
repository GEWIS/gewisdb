<?php

declare(strict_types=1);

namespace Database\Controller;

use Application\Model\Enums\AddressTypes;
use Checker\Service\Checker as CheckerService;
use Checker\Service\Renewal as RenewalService;
use Database\Model\Member as MemberModel;
use Database\Service\Member as MemberService;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;

use function array_map;

/**
 * @method FlashMessenger flashMessenger()
 */
class MemberController extends AbstractActionController
{
    public function __construct(
        private readonly Translator $translator,
        private readonly CheckerService $checkerService,
        private readonly MemberService $memberService,
        private readonly RenewalService $renewalService,
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

                return new ViewModel(['member' => $member]);
            }
        }

        return new ViewModel([
            'form' => $this->memberService->getMemberForm(),
        ]);
    }

    /**
     * (Graduate) renewal action
     * Perhaps also for ordinary -> graduate in the future
     */
    public function renewAction(): ViewModel
    {
        $form = $this->memberService->getRenewalForm((string) $this->params()->fromRoute('token'));
        if (null === $form) {
            return new ViewModel([]);
        }

        $request = $this->getRequest();

        if ($request->isPost()) {
            $form->setMutableData($request->getPost()->toArray());

            // find if there is an earlier member with the same email
            if (
                $this->memberService->getMemberMapper()->hasMemberWith($form->get('email')->getValue())
                || $this->memberService->getProspectiveMemberMapper()->hasMemberWith($form->get('email')->getValue())
            ) {
                $form->get('email')->setMessages(['There already is a member with this email address.']);
            } elseif ($form->isValid()) {
                /** @var MemberModel $updatedMember */
                $updatedMember = $form->getData();
                $this->memberService->getMemberMapper()->persist($updatedMember);
                $form->getActionLink()->used();
                $this->memberService->getActionLinkMapper()->persist($form->getActionLink());
                $this->renewalService->sendRenewalSuccessEmail($form->getActionLink());

                return new ViewModel([
                    'updatedMember' => $updatedMember,
                ]);
            }
        }

        return new ViewModel([
            'form' => $form,
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

        $res = array_map(static function ($member) {
            return $member->toArray();
        }, $res);

        return new JsonModel(['json' => $res]);
    }

    public function searchFilteredAction(): JsonModel
    {
        $query = $this->params()->fromQuery('q');
        $res = $this->memberService->searchFiltered($query);

        $res = array_map(static function ($member) {
            return $member->toArray();
        }, $res);

        return new JsonModel(['json' => $res]);
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
        $hasCorrectInstallations = true;

        if (null === $member) {
            $member = $this->memberService->getMember($lidnr);
            // `$member` is simple and has no correct installations (otherwise it would not have been `null`).
            $hasCorrectInstallations = false;

            if (null === $member) {
                return $this->notFoundAction();
            }
        }

        if ($member->getDeleted()) {
            return $this->memberIsDeleted($member);
        }

        return new ViewModel([
            'member' => $member,
            'hasCorrectInstallations' => $hasCorrectInstallations,
        ]);
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
    public function editAction(): Response|ViewModel
    {
        $member = $this->memberService->getMember((int) $this->params()->fromRoute('id'));

        if (null === $member) {
            return $this->notFoundAction();
        }

        if ($member->getDeleted()) {
            return $this->memberIsDeleted($member);
        }

        if ($this->getRequest()->isPost()) {
            $updatedMember = $this->memberService->edit(
                $member,
                $this->getRequest()->getPost()->toArray(),
            );

            if (null !== $updatedMember) {
                $this->flashMessenger()->addSuccessMessage('Wijzigingen zijn opgeslagen!');

                return $this->redirect()->toRoute('member/show', ['id' => $updatedMember->getLidnr()]);
            }

            $this->flashMessenger()->addSuccessMessage('Wijzigingen kunnen niet worden opgeslagen.');
        }

        return new ViewModel($this->memberService->getMemberEditForm($member));
    }

    /**
     * Delete action.
     *
     * Delete a member.
     */
    public function deleteAction(): Response|ViewModel
    {
        $member = $this->memberService->getMember((int) $this->params()->fromRoute('id'));

        if (null === $member) {
            return $this->notFoundAction();
        }

        if ($member->getDeleted()) {
            return $this->memberIsDeleted($member);
        }

        if ($this->getRequest()->isPost()) {
            $this->memberService->remove($member);

            $this->flashMessenger()->addSuccessMessage('Het lid is succesvol verwijderd.');

            return $this->redirect()->toRoute('member');
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
    public function listsAction(): Response|ViewModel
    {
        $member = $this->memberService->getMember((int) $this->params()->fromRoute('id'));

        if (null === $member) {
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
                $this->flashMessenger()->addSuccessMessage('Aanmeldingen mailinglijsten zijn opgeslagen!');

                return $this->redirect()->toRoute('member/show', ['id' => $member->getLidnr()]);
            }

            $this->flashMessenger()->addSuccessMessage('Aanmeldingen mailinglijsten kunnen niet worden opgeslagen.');
        }

        return new ViewModel($this->memberService->getListForm($member));
    }

    /**
     * Membership action.
     *
     * Update / renew membership.
     */
    public function membershipAction(): Response|ViewModel
    {
        $member = $this->memberService->getMember((int) $this->params()->fromRoute('id'));

        if (null === $member) {
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
                $this->flashMessenger()->addSuccessMessage('Wijziging lidmaatschapstype is opgeslagen!');

                return $this->redirect()->toRoute('member/show', ['id' => $member->getLidnr()]);
            }

            $this->flashMessenger()->addSuccessMessage('Wijziging lidmaatschapstype kan niet worden opgeslagen.');
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
    public function expirationAction(): Response|ViewModel
    {
        $member = $this->memberService->getMember((int) $this->params()->fromRoute('id'));

        if (null === $member) {
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
                $this->flashMessenger()->addSuccessMessage('Nieuwe verloopdatum lidmaatschap is opgeslagen!');

                return $this->redirect()->toRoute('member/show', ['id' => $member->getLidnr()]);
            }

            $this->flashMessenger()->addSuccessMessage('Nieuwe verloopdatum lidmaatschap kan niet worden opgeslagen.');
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
    public function editAddressAction(): Response|ViewModel
    {
        $type = AddressTypes::tryFrom($this->params()->fromRoute('type'));

        if (null === $type) {
            return $this->notFoundAction();
        }

        $member = $this->memberService->getMember((int) $this->params()->fromRoute('id'));

        if (null === $member) {
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
                $this->flashMessenger()->addSuccessMessage('Wijzigingen adres zijn opgeslagen!');

                return $this->redirect()->toRoute('member/show', ['id' => $address->getMember()->getLidnr()]);
            }

            $this->flashMessenger()->addSuccessMessage('Wijzigingen adress kunnen niet worden opgeslagen.');
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
    public function addAddressAction(): Response|ViewModel
    {
        $type = AddressTypes::tryFrom($this->params()->fromRoute('type'));

        if (null === $type) {
            return $this->notFoundAction();
        }

        $member = $this->memberService->getMember((int) $this->params()->fromRoute('id'));

        if (null === $member) {
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
                $this->flashMessenger()->addSuccessMessage('Nieuw adres is opgeslagen!');

                return $this->redirect()->toRoute('member/show', ['id' => $address->getMember()->getLidnr()]);
            }

            $this->flashMessenger()->addSuccessMessage('Nieuw address kan niet worden opgeslagen.');
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
    public function removeAddressAction(): Response|ViewModel
    {
        $type = AddressTypes::tryFrom($this->params()->fromRoute('type'));

        if (null === $type) {
            return $this->notFoundAction();
        }

        $member = $this->memberService->getMember((int) $this->params()->fromRoute('id'));

        if (null === $member) {
            return $this->notFoundAction();
        }

        if ($member->getDeleted()) {
            return $this->memberIsDeleted($member);
        }

        if ($this->getRequest()->isPost()) {
            $updatedMember = $this->memberService->removeAddress(
                $member,
                $type,
                $this->getRequest()->getPost()->toArray(),
            );

            if (null !== $updatedMember) {
                $this->flashMessenger()->addSuccessMessage('Adres is succesvol verwijderd!');

                return $this->redirect()->toRoute('member/show', ['id' => $updatedMember->getLidnr()]);
            }

            $this->flashMessenger()->addSuccessMessage('Address kan niet worden verwijderd.');
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

        return new ViewModel(['username' => $username]);
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

    /**
     * Show all pending member updates.
     */
    public function updatesAction(): ViewModel
    {
        return new ViewModel(['updates' => $this->memberService->getPendingMemberUpdates()]);
    }

    /**
     * Show a specific member update.
     */
    public function showUpdateAction(): ViewModel
    {
        $memberUpdate = $this->memberService->getPendingMemberUpdate((int) $this->params()->fromRoute('id'));

        if (null === $memberUpdate) {
            return $this->notFoundAction();
        }

        $member = $memberUpdate->getMember();

        if (null === $member) {
            return $this->notFoundAction();
        }

        if ($member->getDeleted()) {
            return $this->memberIsDeleted($member);
        }

        return new ViewModel([
            'member' => $member,
            'memberUpdate' => $memberUpdate,
        ]);
    }

    /**
     * Approve a pending member update.
     */
    public function approveUpdateAction(): Response|ViewModel
    {
        $memberUpdate = $this->memberService->getPendingMemberUpdate((int) $this->params()->fromRoute('id'));

        if (null === $memberUpdate) {
            return $this->notFoundAction();
        }

        $member = $memberUpdate->getMember();

        if (null === $member) {
            return $this->notFoundAction();
        }

        if ($member->getDeleted()) {
            return $this->memberIsDeleted($member);
        }

        if ($this->getRequest()->isPost()) {
            $member = $this->memberService->approveMemberUpdate($member, $memberUpdate);

            if (null !== $member) {
                $this->flashMessenger()->addSuccessMessage(
                    $this->translator->translate('The changes have been applied!'),
                );

                return $this->redirect()->toRoute('member/updates');
            }

            $this->flashMessenger()->addErrorMessage(
                $this->translator->translate('An error occurred while trying to save the changes.'),
            );
        }

        return $this->redirect()->toRoute('member/show/update', ['id' => $member->getLidnr()]);
    }

    /**
     * Reject a member update.
     */
    public function rejectUpdateAction(): Response|ViewModel
    {
        $memberUpdate = $this->memberService->getPendingMemberUpdate((int) $this->params()->fromRoute('id'));

        if (null === $memberUpdate) {
            return $this->notFoundAction();
        }

        $member = $memberUpdate->getMember();

        if (null === $member) {
            return $this->notFoundAction();
        }

        if ($member->getDeleted()) {
            return $this->memberIsDeleted($member);
        }

        if ($this->getRequest()->isPost()) {
            $result = $this->memberService->rejectMemberUpdate($memberUpdate);

            if (null !== $result) {
                $this->flashMessenger()->addInfoMessage(
                    $this->translator->translate('The changes have not been applied.'),
                );

                return $this->redirect()->toRoute('member/updates');
            }

            $this->flashMessenger()->addInfoMessage(
                $this->translator->translate('An error occurred while trying to reject the changes.'),
            );
        }

        return $this->redirect()->toRoute('member/show/update', ['id' => $member->getLidnr()]);
    }

    private function memberIsDeleted(MemberModel $member): ViewModel
    {
        $viewModel = new ViewModel(['member' => $member]);
        $viewModel->setTemplate('database/member/deleted.phtml');

        return $viewModel;
    }
}
