<?php

namespace Database\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Database\Model\Member;

class ProspectiveMemberController extends AbstractActionController
{
    /**
     * Index  action.
     */
    public function indexAction()
    {
        return new ViewModel([]);
    }

    /**
     * Search action.
     *
     * Searches for prospective members.
     */
    public function searchAction()
    {
        $service = $this->getMemberService();

        $query = $this->params()->fromQuery('q');

        $res = $service->searchProspective($query);

        $res = array_map(function ($member) {
            return $member->toArray();
        }, $res);

        return new JsonModel([
            'json' => $res
        ]);
    }

    /**
     * Show action.
     *
     * Shows prospective member information.
     */
    public function showAction()
    {
        $service = $this->getMemberService();

        return new ViewModel($service->getProspectiveMember($this->params()->fromRoute('id')));
    }

    /**
     * Show action.
     *
     * Shows prospective member information.
     */
    public function finalizeAction()
    {
        if ($this->getRequest()->isPost()) {
            $service = $this->getMemberService();
            $prospectiveMember = $service->getProspectiveMember($this->params()->fromRoute('id'))['member'];
            $result = $service->finalizeSubscription($this->getRequest()->getPost()->toArray(), $prospectiveMember);

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
        $service = $this->getMemberService();
        $lidnr = $this->params()->fromRoute('id');
        $member = $service->getProspectiveMember($lidnr);
        $member = $member['member'];

        $service->removeProspective($member);
        return $this->redirect()->toRoute('prospective-member');
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
