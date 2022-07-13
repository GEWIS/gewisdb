<?php

namespace Database\Controller;

use Database\Service\Meeting as MeetingService;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ExportController extends AbstractActionController
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
     * Index action.
     */
    public function indexAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->meetingService->export($this->getRequest()->getPost());

            if (null !== $data) {
                return new ViewModel(array(
                    'data' => $data
                ));
            }
        }
        return new ViewModel(array(
            'form' => $this->meetingService->getExportForm()
        ));
    }
}
