<?php

namespace Database\Controller;

use Database\Service\Meeting as MeetingService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\View\Model\JsonModel;

class OrganController extends AbstractActionController
{
    /** @var MeetingService $meetingService */
    private $meetingService;

    /**
     * @param MeetingService $meetingService
     */
    public function __construct(MeetingService $meetingService)
    {
        $this->meetingService = $meetingService;
    }

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
        return new ViewModel([
            'foundation' => $this->meetingService->findFoundation(
                $this->params()->fromRoute('type'),
                $this->params()->fromRoute('number'),
                $this->params()->fromRoute('point'),
                $this->params()->fromRoute('decision'),
                $this->params()->fromRoute('subdecision'),
            ),
        ]);
    }

    /**
     * Get organ info.
     */
    public function infoAction()
    {
        $foundation = $this->meetingService->findFoundation(
            $this->params()->fromRoute('type'),
            $this->params()->fromRoute('number'),
            $this->params()->fromRoute('point'),
            $this->params()->fromRoute('decision'),
            $this->params()->fromRoute('subdecision'),
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
                    'member' => $reference->getMember()->toArray(),
                ];
            }
        }

        return new JsonModel([
            'json' => $data,
        ]);
    }

    /**
     * Search action.
     *
     * Uses JSON to search for members.
     */
    public function searchAction()
    {
        $query = $this->params()->fromQuery('q');
        $res = $this->meetingService->organSearch($query);

        $res = array_map(function ($organ) {
            return $organ->toArray();
        }, $res);

        return new JsonModel([
            'json' => $res,
        ]);
    }
}
