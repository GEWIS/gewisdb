<?php

declare(strict_types=1);

namespace Database\Controller;

use Application\Model\Enums\MeetingTypes;
use Database\Form\AbstractDecision as AbstractDecisionForm;
use Database\Form\Fieldset\MemberFunction as MemberFunctionFieldset;
use Database\Model\Meeting as MeetingModel;
use Database\Service\Api as ApiService;
use Database\Service\Meeting as MeetingService;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;

use function array_map;

class MeetingController extends AbstractActionController
{
    public function __construct(
        private readonly ApiService $apiService,
        private readonly MeetingService $meetingService,
        private readonly MemberFunctionFieldset $memberFunctionFieldset,
    ) {
    }

    /**
     * Index action.
     *
     * Shows all meetings.
     */
    public function indexAction(): ViewModel
    {
        return new ViewModel([
            'meetings' => $this->meetingService->getAllMeetings(),
        ]);
    }

    /**
     * Create a new meeting.
     */
    public function createAction(): HttpResponse|ViewModel
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $meeting = $this->meetingService->createMeeting($request->getPost()->toArray());

            if (null !== $meeting) {
                return $this->redirect()->toRoute('meeting/view', [
                    'type' => $meeting->getType()->value,
                    'number' => $meeting->getNumber(),
                ]);
            }
        }

        return new ViewModel([
            'form' => $this->meetingService->getCreateMeetingForm(),
        ]);
    }

    /**
     * View a meeting.
     */
    public function viewAction(): ViewModel
    {
        $type = MeetingTypes::from($this->params()->fromRoute('type'));
        $number = (int) $this->params()->fromRoute('number');

        $meeting = $this->meetingService->getMeeting(
            $type,
            $number,
        );

        if (null === $meeting) {
            return $this->notFoundAction();
        }

        return new ViewModel([
            'type' => $type,
            'number' => $number,
            'meeting' => $meeting,
        ]);
    }

    /**
     * Decision action.
     */
    public function decisionAction(): ViewModel
    {
        $type = MeetingTypes::from($this->params()->fromRoute('type'));
        $number = (int) $this->params()->fromRoute('number');
        $point = (int) $this->params()->fromRoute('point');
        $decision = (int) $this->params()->fromRoute('decision');

        $meeting = $this->meetingService->getMeeting($type, $number);

        if (null === $meeting) {
            return $this->notFoundAction();
        }

        if ($this->meetingService->decisionExists($type, $number, $point, $decision)) {
            return new ViewModel([
                'meeting' => $meeting,
                'point' => $point,
                'decision' => $decision,
                'error' => true,
            ]);
        }

        return new ViewModel([
            'meeting' => $meeting,
            'point' => $point,
            'decision' => $decision,
            'forms' => $this->getDecisionForms($meeting, $point, $decision),
            'installs' => $this->meetingService->getCurrentBoard(),
            'installs_filtered' => $this->meetingService->getCurrentBoardNotYetReleased(),
            'grants' => $this->meetingService->getCurrentKeys(),
            'memberfunction' => $this->memberFunctionFieldset,
        ]);
    }

    /**
     * Get all forms for a decision action.
     *
     * @return array<string, AbstractDecisionForm>
     */
    protected function getDecisionForms(
        MeetingModel $meeting,
        int $point,
        int $decision,
    ): array {
        $forms = [
            'budget' => $this->meetingService->getBudgetForm(),
            'organ_regulation' => $this->meetingService->getRegulationForm(),
            'foundation' => $this->meetingService->getFoundationForm(),
            'abolish' => $this->meetingService->getAbolishForm(),
            'annulment' => $this->meetingService->getAnnulmentForm(),
            'install' => $this->meetingService->getInstallForm(),
            'key_grant' => $this->meetingService->getKeyGrantForm(),
            'key_withdraw' => $this->meetingService->getKeyWithdrawForm(),
            'minutes' => $this->meetingService->getMinutesForm(),
            'other' => $this->meetingService->getOtherForm(),
            'board_install' => $this->meetingService->getBoardInstallForm(),
            'board_release' => $this->meetingService->getBoardReleaseForm(),
            'board_discharge' => $this->meetingService->getBoardDischargeForm(),
        ];

        foreach ($forms as $form) {
            $form->setDecisionData($meeting, $point, $decision);
        }

        return $forms;
    }

    /**
     * Decision form action.
     */
    public function decisionformAction(): HttpResponse|ViewModel
    {
        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toRoute('meeting');
        }

        /** If we are entering a decision, we pause sync for a while */
        $this->apiService->pauseSync(15);

        return match ($this->params()->fromRoute('form')) {
            'budget' => new ViewModel(
                $this->meetingService->budgetDecision($this->getRequest()->getPost()->toArray()),
            ),
            'organ_regulation' => new ViewModel(
                $this->meetingService->regulationDecision($this->getRequest()->getPost()->toArray()),
            ),
            'foundation' => new ViewModel(
                $this->meetingService->foundationDecision($this->getRequest()->getPost()->toArray()),
            ),
            'abolish' => new ViewModel(
                $this->meetingService->abolishDecision($this->getRequest()->getPost()->toArray()),
            ),
            'install' => new ViewModel(
                $this->meetingService->installDecision($this->getRequest()->getPost()->toArray()),
            ),
            'annulment' => new ViewModel(
                $this->meetingService->annulDecision($this->getRequest()->getPost()->toArray()),
            ),
            'key_grant' => new ViewModel(
                $this->meetingService->keyGrantDecision($this->getRequest()->getPost()->toArray()),
            ),
            'key_withdraw' => new ViewModel(
                $this->meetingService->keyWithdrawDecision($this->getRequest()->getPost()->toArray()),
            ),
            'board_install' => new ViewModel(
                $this->meetingService->boardInstallDecision($this->getRequest()->getPost()->toArray()),
            ),
            'board_release' => new ViewModel(
                $this->meetingService->boardReleaseDecision($this->getRequest()->getPost()->toArray()),
            ),
            'board_discharge' => new ViewModel(
                $this->meetingService->boardDischargeDecision($this->getRequest()->getPost()->toArray()),
            ),
            'other' => new ViewModel(
                $this->meetingService->otherDecision($this->getRequest()->getPost()->toArray()),
            ),
            'minutes' => new ViewModel(
                $this->meetingService->minutesDecision($this->getRequest()->getPost()->toArray()),
            ),
            default => $this->redirect()->toRoute('meeting'),
        };
    }

    /**
     * Delete a decision.
     */
    public function deleteAction(): ViewModel
    {
        $type = MeetingTypes::from($this->params()->fromRoute('type'));
        $number = (int) $this->params()->fromRoute('number');
        $point = (int) $this->params()->fromRoute('point');
        $decision = (int) $this->params()->fromRoute('decision');

        if ($this->getRequest()->isPost()) {
            try {
                if (
                    $this->meetingService->deleteDecision(
                        $this->getRequest()->getPost()->toArray(),
                        $type,
                        $number,
                        $point,
                        $decision,
                    )
                ) {
                    /** If a decision was deleted, pause sync */
                    $this->apiService->pauseSync(60);

                    return new ViewModel([
                        'type' => $type,
                        'number' => $number,
                        'point' => $point,
                        'decision' => $decision,
                    ]);
                }
            } catch (ForeignKeyConstraintViolationException $e) {
                return new ViewModel([
                    'error' => true,
                    'exception' => $e,
                ]);
            }

            // Not deleted
            // TODO: redirect back to meeting
        }

        return new ViewModel([
            'form' => $this->meetingService->getDeleteDecisionForm(),
            'type' => $type,
            'number' => $number,
            'point' => $point,
            'decision' => $decision,
        ]);
    }

    /**
     * Search action for meetings.
     *
     * Uses JSON to search for decisions.
     */
    public function searchMeetingAction(): JsonModel
    {
        $query = $this->params()->fromQuery('q');
        $res = $this->meetingService->meetingSearch($query);

        $res = array_map(static function ($meeting) {
            return $meeting->toArray();
        }, $res);

        return new JsonModel(['json' => $res]);
    }

    /**
     * Search action for decisions.
     *
     * Uses JSON to search for decisions.
     */
    public function searchDecisionAction(): JsonModel
    {
        $query = $this->params()->fromQuery('q');
        $res = $this->meetingService->decisionSearch($query);

        $res = array_map(static function ($decision) {
            return $decision->toArray();
        }, $res);

        return new JsonModel(['json' => $res]);
    }
}
