<?php

namespace Database\Controller;

use Database\Model\Member;
use Database\Service\Member as MemberService;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class ProspectiveMemberController extends AbstractActionController
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
     * Index  action.
     */
    public function indexAction()
    {
        return new ViewModel(array());
    }

    /**
     * Search action.
     *
     * Searches for prospective members.
     */
    public function searchAction()
    {
        $query = $this->params()->fromQuery('q');
        $res = $this->memberService->searchProspective($query);

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
     * Shows prospective member information.
     */
    public function showAction()
    {
        return new ViewModel($this->memberService->getProspectiveMember($this->params()->fromRoute('id')));
    }

    /**
     * Show action.
     *
     * Shows prospective member information.
     */
    public function finalizeAction()
    {
        if ($this->getRequest()->isPost()) {
            $prospectiveMember = $this->memberService->getProspectiveMember($this->params()->fromRoute('id'))['member'];
            $result = $this->memberService->finalizeSubscription($this->getRequest()->getPost()->toArray(), $prospectiveMember);

            if (null !== $result) {
                return $this->redirect()->toRoute('member/show', [
                    'id' => $result->getLidnr()
                ]);
            }
        }

        return $this->redirect()->toRoute('prospective-member/show', [
            'id' => $this->params()->fromRoute('id')
        ]);
    }

    /**
     * Delete action.
     *
     * Delete a prospective member.
     */
    public function deleteAction()
    {
        $lidnr = $this->params()->fromRoute('id');
        $member = $this->memberService->getProspectiveMember($lidnr);
        $member = $member['member'];

        $this->memberService->removeProspective($member);
        return $this->redirect()->toRoute('prospective-member');
    }
}
