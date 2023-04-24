<?php

declare(strict_types=1);

namespace Database\Controller;

use Database\Service\Member as MemberService;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\{
    ViewModel,
    JsonModel,
};

class ProspectiveMemberController extends AbstractActionController
{
    public function __construct(private readonly MemberService $memberService)
    {
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
        $res = $this->memberService->searchProspective($query);

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
     * Shows prospective member information.
     */
    public function showAction(): ViewModel
    {
        return new ViewModel($this->memberService->getProspectiveMember((int) $this->params()->fromRoute('id')));
    }

    /**
     * Show action.
     *
     * Shows prospective member information.
     */
    public function finalizeAction(): Response
    {
        $lidnr = (int) $this->params()->fromRoute('id');

        if ($this->getRequest()->isPost()) {
            $prospectiveMember = $this->memberService->getProspectiveMember($lidnr)['member'];
            $result = $this->memberService->finalizeSubscription(
                $this->getRequest()->getPost()->toArray(),
                $prospectiveMember,
            );

            if (null !== $result) {
                $this->memberService->sendMemberConfirmedEmail($result);

                return $this->redirect()->toRoute('member/show', [
                    'id' => $result->getLidnr(),
                ]);
            }
        }

        return $this->redirect()->toRoute('prospective-member/show', [
            'id' => $lidnr,
        ]);
    }

    /**
     * Delete action.
     *
     * Delete a prospective member.
     */
    public function deleteAction(): Response
    {
        $lidnr = (int) $this->params()->fromRoute('id');
        $member = $this->memberService->getProspectiveMember($lidnr);
        $member = $member['member'];

        if ($member !== null) {
            $this->memberService->removeProspective($member);
        }

        return $this->redirect()->toRoute('prospective-member');
    }
}
