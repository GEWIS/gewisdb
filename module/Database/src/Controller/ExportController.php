<?php

namespace Database\Controller;

use Database\Service\Meeting as MeetingService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class ExportController extends AbstractActionController
{
    private MeetingService $meetingService;

    public function __construct(MeetingService $meetingService)
    {
        $this->meetingService = $meetingService;
    }

    /**
     * Index action.
     */
    public function indexAction(): ViewModel
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->meetingService->export($this->getRequest()->getPost()->toArray());

            if (null !== $data) {
                return new ViewModel([
                    'data' => $data,
                ]);
            }
        }
        return new ViewModel([
            'form' => $this->meetingService->getExportForm(),
        ]);
    }
}
