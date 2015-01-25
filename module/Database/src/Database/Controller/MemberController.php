<?php

namespace Database\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

use Database\Model\Member;

class MemberController extends AbstractActionController
{

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
        $service = $this->getMemberService();

        if ($request->isPost()) {
            $member = $service->subscribe($request->getPost());

            if (null !== $member) {
                return new ViewModel(array(
                    'member' => $member
                ));
            }
        }

        return new ViewModel(array(
            'form' => $this->getMemberService()->getMemberForm()
        ));
    }

    /**
     * Search action.
     *
     * Searches for members.
     */
    public function searchAction()
    {
        $service = $this->getMemberService();

        $query = $this->params()->fromQuery('q');

        $res = $service->search($query);

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
        $service = $this->getMemberService();

        return new ViewModel(array(
            'member' => $service->getMember($this->params()->fromRoute('id'))
        ));
    }

    /**
     * Edit action.
     *
     * Edit member information.
     */
    public function editAction()
    {
        $service = $this->getMemberService();

        $member = $service->getMember($this->params()->fromRoute('id'));
        $form = $service->getMemberEditForm();
        $form->bind($member);

        return new ViewModel(array(
            'member' => $member,
            'form' => $form
        ));
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
