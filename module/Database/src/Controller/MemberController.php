<?php

declare(strict_types=1);

namespace Database\Controller;

use Application\Model\Enums\AddressTypes;
use Checker\Service\Checker as CheckerService;
use Database\Model\Member as MemberModel;
use Database\Service\Member as MemberService;
use Database\Service\Stripe as StripeService;
use DateTime;
use Laminas\Http\Header\HeaderInterface;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\Stdlib\ResponseInterface;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;

use function array_map;
use function intval;
use function ip2long;
use function sprintf;

/**
 * @method FlashMessenger flashMessenger()
 */
class MemberController extends AbstractActionController
{
    public function __construct(
        private readonly Translator $translator,
        private readonly CheckerService $checkerService,
        private readonly MemberService $memberService,
        private readonly StripeService $stripeService,
        private readonly string $remoteAddress,
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
    public function subscribeAction(): HttpResponse|ViewModel
    {
        $ipMCS = ip2long($this->remoteAddress) >= ip2long('131.155.68.0')
            && ip2long($this->remoteAddress) <= ip2long('131.155.71.255');
        if (7 === intval((new DateTime())->format('n')) && !$ipMCS) {
            return (new ViewModel())->setTemplate('database/member/subscribe-disabled');
        }

        $request = $this->getRequest();

        if ($request->isPost()) {
            $prospectiveMember = $this->memberService->subscribe($request->getPost()->toArray());

            if (null !== $prospectiveMember) {
                // Always send the enrolment e-mail to ensure that the prospective member has a payment link that can be
                // used in the event the checkout did not succeed.
                $this->memberService->sendRegistrationUpdateEmail(
                    $prospectiveMember,
                    'registration',
                );

                // Create Stripe checkout session.
                $checkoutLink = $this->stripeService->getCheckoutLink($prospectiveMember);

                if (null === $checkoutLink) {
                    // We have failed to generate a payment link, however, as we have already persisted the prospective
                    // member we still want to show them something useful. They should have already received the e-mail
                    // containing the generic payment link, which they can use to (re)start the checkout flow.

                    return $this->redirect()->toRoute('member/subscribe/checkout/status', ['status' => 'error']);
                }

                $view = new ViewModel([
                    'destination' => $this->translator->translate('our payment provider'),
                    'url' => $checkoutLink,
                ]);
                $view->setTemplate('redirect');

                return $view;
            }
        }

        return new ViewModel([
            'form' => $this->memberService->getMemberForm(),
        ]);
    }

    public function checkoutStatusAction(): ViewModel
    {
        $status = $this->params()->fromRoute('status');
        $checkoutSessionId = (string) $this->params()->fromQuery('stripe_session_id');
        $prospectiveMemberId = $this->stripeService->getLidnrFromCheckoutSession($checkoutSessionId);

        if (null !== $prospectiveMemberId) {
            $prospectiveMember = $this->memberService->getProspectiveMember($prospectiveMemberId)['member'];
        } else {
            $prospectiveMember = null;
        }

        // We assume that an empty array means an error state (`$status` will be "error" but not compared).
        $results = ['prospectiveMember' => $prospectiveMember];
        if ('cancelled' === $status) {
            $results['cancelled'] = true;
        } elseif ('completed' === $status) {
            $results['completed'] = true;
        }

        return new ViewModel($results);
    }

    public function checkoutRestartAction(): HttpResponse|ViewModel
    {
        $token = (string) $this->params()->fromRoute('token');
        $paymentLink = $this->stripeService->getPaymentLink($token);

        if (
            null === $paymentLink
            || $paymentLink->isUsed()
        ) {
            return new ViewModel(['error' => false]);
        }

        $restartedCheckoutLink = $this->stripeService->restartCheckoutLink($paymentLink->getProspectiveMember());

        if (null === $restartedCheckoutLink) {
            return new ViewModel(['error' => true]);
        }

        return $this->redirect()
            ->toUrl($restartedCheckoutLink)
            ->setStatusCode(HttpResponse::STATUS_CODE_303);
    }

    public function paymentWebhookAction(): ResponseInterface
    {
        $signature = $this->getRequest()->getHeader('Stripe-Signature');

        if ($signature instanceof HeaderInterface) {
            $event = $this->stripeService->verifyEvent($this->getRequest()->getContent(), $signature->getFieldValue());

            if (null !== $event) {
                // Stripe technically wants the 200 before we handle things, however, Laminas has as far as I know no
                // (good) support for using Fibers to do this concurrently.
                $this->stripeService->handleEvent($event);

                // We know this is always going to be an HttpResponse
                $response = $this->getResponse();
                if ($response instanceof HttpResponse) {
                    $response->setStatusCode(HttpResponse::STATUS_CODE_200);
                }

                return $response;
            }
        }

        // We know this is always going to be an HttpResponse
        $response = $this->getResponse();
        if ($response instanceof HttpResponse) {
            $response->setStatusCode(HttpResponse::STATUS_CODE_400);
        }

        return $response;
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
            $oldEmail = $form->get('email')->getValue();
            $form->setMutableData($request->getPost()->toArray());

            // if we changed our email
            // check if it is not in use by someone else
            if (
                $oldEmail !== $form->get('email')->getValue()
                && (
                    $this->memberService->getMemberMapper()->hasMemberWith($form->get('email')->getValue())
                    || $this->memberService->getProspectiveMemberMapper()->hasMemberWith(
                        $form->get('email')->getValue(),
                    )
                )
            ) {
                $form->get('email')->setMessages(['There already is a member with this email address.']);
            } elseif ($form->isValid()) {
                /** @var MemberModel $updatedMember */
                $updatedMember = $form->getData();
                $renewalLink = $form->getRenewalLink();
                $this->memberService->renewMember($updatedMember, $renewalLink);

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
    public function showAction(): HttpResponse|ViewModel
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

        $noteForm = $this->memberService->getAuditNoteForm($member);
        if ($this->getRequest()->isPost() && 'new-auditentry' === $this->getRequest()->getPost('submit', '')) {
            $noteForm->setData($this->getRequest()->getPost()->toArray());

            if ($noteForm->isValid()) {
                $this->memberService->addAuditNote($member, $noteForm);
                $this->flashMessenger()->addSuccessMessage(
                    sprintf(
                        $this->translator->translate('%s has been added to %s'),
                        $this->translator->translate('Note'),
                        $this->translator->translate('member'),
                    ),
                );

                return $this->redirect()->toRoute('member/show', [
                    'id' => $lidnr,
                ]);
            }
        }

        return new ViewModel([
            'member' => $member,
            'hasCorrectInstallations' => $hasCorrectInstallations,
            'noteForm' => $noteForm,
        ]);
    }

    /**
     * Toggle supremum action.
     *
     * Toggles if a member wants a supremum
     */
    public function setSupremumAction(): HttpResponse|ViewModel
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
    public function editAction(): HttpResponse|ViewModel
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
                $this->flashMessenger()->addSuccessMessage(
                    sprintf(
                        $this->translator->translate('Change(s) of %s have been saved!'),
                        $this->translator->translate('member'),
                    ),
                );

                return $this->redirect()->toRoute('member/show', ['id' => $updatedMember->getLidnr()]);
            }

            $this->flashMessenger()->addErrorMessage(
                sprintf(
                    $this->translator->translate('Could not save change(s) of %s!'),
                    $this->translator->translate('member'),
                ),
            );
        }

        return new ViewModel($this->memberService->getMemberEditForm($member));
    }

    /**
     * Delete action.
     *
     * Delete a member.
     */
    public function deleteAction(): HttpResponse|ViewModel
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

            $this->flashMessenger()->addSuccessMessage(
                sprintf(
                    $this->translator->translate('Succesfully deleted %s!'),
                    $this->translator->translate('member'),
                ),
            );

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
    public function listsAction(): HttpResponse|ViewModel
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
                $this->flashMessenger()->addSuccessMessage(
                    sprintf(
                        $this->translator->translate('Change(s) of %s have been saved!'),
                        $this->translator->translate('mailing list subscriptions'),
                    ),
                );

                return $this->redirect()->toRoute('member/show', ['id' => $member->getLidnr()]);
            }

            $this->flashMessenger()->addErrorMessage(
                sprintf(
                    $this->translator->translate('Could not save change(s) of %s!'),
                    $this->translator->translate('mailing list subscriptions'),
                ),
            );
        }

        return new ViewModel($this->memberService->getListForm($member));
    }

    /**
     * Membership action.
     *
     * Update / renew membership.
     */
    public function membershipAction(): HttpResponse|ViewModel
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
                $renewalLinks = $this->memberService->getActionLinkMapper()
                    ->findRenewalByMember($member->getLidnr());

                foreach ($renewalLinks as $renewalLink) {
                    $renewalLink->setUsed(true);
                    $this->memberService->getActionLinkMapper()->persist($renewalLink);
                }

                $this->flashMessenger()->addSuccessMessage(
                    sprintf(
                        $this->translator->translate('Change(s) of %s have been saved!'),
                        $this->translator->translate('membership type'),
                    ),
                );

                return $this->redirect()->toRoute('member/show', ['id' => $member->getLidnr()]);
            }

            $this->flashMessenger()->addErrorMessage(
                sprintf(
                    $this->translator->translate('Could not save change(s) of %s!'),
                    $this->translator->translate('membership type'),
                ),
            );
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
    public function expirationAction(): HttpResponse|ViewModel
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
                $this->flashMessenger()->addSuccessMessage(
                    sprintf(
                        $this->translator->translate('Change(s) of %s have been saved!'),
                        $this->translator->translate('membership expiration date'),
                    ),
                );

                return $this->redirect()->toRoute('member/show', ['id' => $member->getLidnr()]);
            }

            $this->flashMessenger()->addErrorMessage(
                sprintf(
                    $this->translator->translate('Could not save change(s) of %s!'),
                    $this->translator->translate('membership expiration date'),
                ),
            );
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
    public function editAddressAction(): HttpResponse|ViewModel
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
                $this->flashMessenger()->addSuccessMessage(
                    sprintf(
                        $this->translator->translate('Change(s) of %s have been saved!'),
                        $this->translator->translate('member address'),
                    ),
                );

                return $this->redirect()->toRoute('member/show', ['id' => $address->getMember()->getLidnr()]);
            }

            $this->flashMessenger()->addErrorMessage(
                sprintf(
                    $this->translator->translate('Could not save change(s) of %s!'),
                    $this->translator->translate('member address'),
                ),
            );
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
    public function addAddressAction(): HttpResponse|ViewModel
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
                $this->flashMessenger()->addSuccessMessage(
                    sprintf(
                        $this->translator->translate('%s has been added to %s'),
                        $this->translator->translate('Address'),
                        $this->translator->translate('member'),
                    ),
                );

                return $this->redirect()->toRoute('member/show', ['id' => $address->getMember()->getLidnr()]);
            }

            $this->flashMessenger()->addErrorMessage(
                sprintf(
                    $this->translator->translate('Could not add new %s!'),
                    $this->translator->translate('member address'),
                ),
            );
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
    public function removeAddressAction(): HttpResponse|ViewModel
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
                $this->flashMessenger()->addSuccessMessage(
                    sprintf(
                        $this->translator->translate('Succesfully deleted %s!'),
                        $this->translator->translate('member address'),
                    ),
                );

                return $this->redirect()->toRoute('member/show', ['id' => $updatedMember->getLidnr()]);
            }

            $this->flashMessenger()->addErrorMessage(
                sprintf(
                    $this->translator->translate('Could not delete %s!'),
                    $this->translator->translate('member address'),
                ),
            );
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
    public function approveUpdateAction(): HttpResponse|ViewModel
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
                    sprintf(
                        $this->translator->translate('Change(s) of %s have been approved and saved!'),
                        $this->translator->translate('member'),
                    ),
                );

                return $this->redirect()->toRoute('member/updates');
            }

            $this->flashMessenger()->addErrorMessage(
                sprintf(
                    $this->translator->translate('Could not save change(s) of %s!'),
                    $this->translator->translate('member'),
                ),
            );
        }

        return $this->redirect()->toRoute('member/show/update', ['id' => $member->getLidnr()]);
    }

    /**
     * Reject a member update.
     */
    public function rejectUpdateAction(): HttpResponse|ViewModel
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
                    sprintf(
                        $this->translator->translate('Change(s) of %s have been rejected!'),
                        $this->translator->translate('member'),
                    ),
                );

                return $this->redirect()->toRoute('member/updates');
            }

            $this->flashMessenger()->addErrorMessage(
                sprintf(
                    $this->translator->translate('Could not reject change(s) of %s!'),
                    $this->translator->translate('member'),
                ),
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
