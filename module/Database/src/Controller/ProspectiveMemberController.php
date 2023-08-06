<?php

declare(strict_types=1);

namespace Database\Controller;

use Database\Service\Member as MemberService;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;

use function array_map;

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
    public function finalizeAction(): Response
    {
        $lidnr = (int) $this->params()->fromRoute('id');

        if ($this->getRequest()->isPost()) {
            $prospectiveMember = $this->memberService->getProspectiveMember($lidnr)['member'];

            if (null === $prospectiveMember) {
                return $this->redirect()->toRoute('prospective-member');
            }

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

        return $this->redirect()->toRoute('prospective-member/show', ['id' => $lidnr]);
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

        if (null !== $member) {
            $this->memberService->removeProspective($member);
        }

        return $this->redirect()->toRoute('prospective-member');
    }
}
