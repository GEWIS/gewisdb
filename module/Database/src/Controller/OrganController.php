<?php

namespace Database\Controller;

use Application\Model\Enums\MeetingTypes;
use Database\Model\SubDecision\Installation as InstallationModel;
use Database\Service\Meeting as MeetingService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\{
    ViewModel,
    JsonModel,
};

class OrganController extends AbstractActionController
{
    private MeetingService $meetingService;

    public function __construct(MeetingService $meetingService)
    {
        $this->meetingService = $meetingService;
    }

    /**
     * Index action, for organ search.
     */
    public function indexAction(): ViewModel
    {
        return new ViewModel([]);
    }

    /**
     * View an organ.
     */
    public function viewAction(): ViewModel
    {
        return new ViewModel([
            'foundation' => $this->meetingService->findFoundation(
                MeetingTypes::from($this->params()->fromRoute('type')),
                (int) $this->params()->fromRoute('number'),
                (int) $this->params()->fromRoute('point'),
                (int) $this->params()->fromRoute('decision'),
                (int) $this->params()->fromRoute('subdecision'),
            ),
        ]);
    }

    /**
     * Get organ info.
     */
    public function infoAction(): JsonModel
    {
        $foundation = $this->meetingService->findFoundation(
            MeetingTypes::from($this->params()->fromRoute('type')),
            (int) $this->params()->fromRoute('number'),
            (int) $this->params()->fromRoute('point'),
            (int) $this->params()->fromRoute('decision'),
            (int) $this->params()->fromRoute('subdecision'),
        );

        $data = $foundation->toArray();
        $data['members'] = [];

        foreach ($foundation->getReferences() as $reference) {
            if ($reference instanceof InstallationModel) {
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
    public function searchAction(): JsonModel
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
