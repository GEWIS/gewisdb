<?php

namespace Database\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class OrganController extends AbstractActionController
{
    /**
     * Index action, for organ search.
     */
    public function indexAction()
    {
        return new ViewModel([]);
    }

    /**
     * View an organ.
     */
    public function viewAction()
    {
        $service = $this->getMeetingService();

        return new ViewModel([
            'foundation' => $service->findFoundation(
                $this->params()->fromRoute('type'),
                $this->params()->fromRoute('number'),
                $this->params()->fromRoute('point'),
                $this->params()->fromRoute('decision'),
                $this->params()->fromRoute('subdecision')
            )
        ]);
    }

    /**
     * Get organ info.
     */
    public function infoAction()
    {
        $service = $this->getMeetingService();

        $foundation = $service->findFoundation(
            $this->params()->fromRoute('type'),
            $this->params()->fromRoute('number'),
            $this->params()->fromRoute('point'),
            $this->params()->fromRoute('decision'),
            $this->params()->fromRoute('subdecision')
        );
        $data = $foundation->toArray();
        $data['members'] = [];

        foreach ($foundation->getReferences() as $reference) {
            if ($reference instanceof \Database\Model\SubDecision\Installation) {
                $data['members'][] = [
                    'meeting_type' => $reference->getDecision()->getMeeting()->getType(),
                    'meeting_number' => $reference->getDecision()->getMeeting()->getNumber(),
                    'decision_point' => $reference->getDecision()->getPoint(),
                    'decision_number' => $reference->getDecision()->getNumber(),
                    'subdecision_number' => $reference->getNumber(),
                    'function' => $reference->getFunction(),
                    'member' => $reference->getMember()->toArray()
                ];
            }
        }

        return new JsonModel([
            'json' => $data
        ]);
    }

    /**
     * Search action.
     *
     * Uses JSON to search for members.
     */
    public function searchAction()
    {
        $service = $this->getMeetingService();

        $query = $this->params()->fromQuery('q');

        $res = $service->organSearch($query);

        $res = array_map(function ($organ) {
            return $organ->toArray();
        }, $res);

        return new JsonModel([
            'json' => $res
        ]);
    }

    /**
     * Get the meeting service.
     *
     * @return \Database\Service\Meeting
     */
    public function getMeetingService()
    {
        return $this->getServiceLocator()->get('database_service_meeting');
    }
}
