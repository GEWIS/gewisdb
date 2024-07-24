<?php

declare(strict_types=1);

namespace Database\Controller;

use Database\Model\ProspectiveMember as ProspectiveMemberModel;
use Database\Service\Member as MemberService;
use Database\Service\Stripe as StripeService;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;

use function array_map;

class ProspectiveMemberController extends AbstractActionController
{
    public function __construct(
        private readonly Translator $translator,
        private readonly MemberService $memberService,
        private readonly StripeService $stripeService,
    ) {
    }

    /**
     * Index  action.
     */
    public function indexAction(): ViewModel
    {
        return new ViewModel([]);
    }

    /**
     * Search action.
     *
     * Searches for prospective members.
     */
    public function searchAction(): JsonModel
    {
        $query = $this->params()->fromQuery('q');
        $type = $this->params()->fromQuery('type');

        $res = $this->memberService->searchProspective($query, $type);

        $res = array_map(static function ($member) {
            return $member->toArray();
        }, $res);

        return new JsonModel(['json' => $res]);
    }

    /**
     * Show action.
     *
     * Shows prospective member information.
     */
    public function showAction(): ViewModel
    {
        $result = $this->memberService->getProspectiveMember((int) $this->params()->fromRoute('id'));

        if (null === $result['member']) {
            return $this->notFoundAction();
        }

        return new ViewModel($result);
    }

    /**
     * Show action.
     *
     * Shows prospective member information.
     */
    public function finalizeAction(): HttpResponse
    {
        $lidnr = (int) $this->params()->fromRoute('id');

        if ($this->getRequest()->isPost()) {
            /** @var ProspectiveMemberModel|null $prospectiveMember */
            $prospectiveMember = $this->memberService->getProspectiveMember($lidnr)['member'];

            if (null === $prospectiveMember) {
                return $this->redirect()->toRoute('prospective-member');
            }

            if ($prospectiveMember->canBeApproved()) {
                $result = $this->memberService->finalizeSubscription(
                    $this->getRequest()->getPost()->toArray(),
                    $prospectiveMember,
                );

                if (null !== $result) {
                    $this->memberService->sendRegistrationUpdateEmail(
                        $result,
                        'welcome',
                    );

                    return $this->redirect()->toRoute('member/show', [
                        'id' => $result->getLidnr(),
                    ]);
                }
            }
        }

        return $this->redirect()->toRoute('prospective-member/show', ['id' => $lidnr]);
    }

    /**
     * Delete action.
     *
     * Delete a prospective member.
     */
    public function deleteAction(): HttpResponse|ViewModel
    {
        $lidnr = (int) $this->params()->fromRoute('id');
        $prospectiveMember = $this->memberService->getProspectiveMember($lidnr);
        $prospectiveMember = $prospectiveMember['member'];

        if (
            null === $prospectiveMember
            || !$prospectiveMember->canBeDeleted()
        ) {
            return $this->redirect()->toRoute('prospective-member/show', ['id' => $lidnr]);
        }

        if ($prospectiveMember->hasPaid()) {
            // If the prospective member has paid, we need to do some work to get their fee refunded.
            $hasRefund = $this->stripeService->hasRefund($prospectiveMember);

            if (null === $hasRefund) {
                // Cannot proceed, show error that we cannot determine state of refund.
                return new ViewModel([
                    'title' => $this->translator->translate('Unable to check refund status'),
                    // phpcs:ignore -- user-visible strings should not be split
                    'description' => $this->translator->translate('We were unable to determine whether the prospective member has already received a refund. Please try again later. If this error stays, contact the ApplicatieBeheerCommissie and/or treasurer for more information.'),
                ]);
            }

            if (!$hasRefund) {
                // No refund yet, create one.
                $refund = $this->stripeService->createRefund($prospectiveMember);

                if (null === $refund) {
                    // Cannot proceed, show error that we could not create the refund.
                    return new ViewModel([
                        'title' => $this->translator->translate('Unable to create refund'),
                        // phpcs:ignore -- user-visible strings should not be split
                        'description' => $this->translator->translate('We were unable to create a refund for the prospective member. Please try again later. If this error stays, contact the ApplicatieBeheerCommissie and/or treasurer for more information.'),
                    ]);
                }
            }
        }

        // Remove the prospective member. This happens when the prospective member has not paid, or they have paid and
        // we created a refund for that payment.
        $this->memberService->removeProspective($prospectiveMember);

        return $this->redirect()->toRoute('prospective-member');
    }
}
