<?php

declare(strict_types=1);

namespace Database\Controller;

use Database\Model\Enums\InstallationFunctions;
use Database\Service\MailingList as MailingListService;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\View\Model\ViewModel;

class SettingsController extends AbstractActionController
{
    public function __construct(
        private readonly MvcTranslator $translator,
        private readonly MailingListService $mailingListService,
    ) {
    }

    /**
     * Index action.
     */
    public function indexAction(): ViewModel
    {
        return new ViewModel([]);
    }

    /**
     * Function action.
     */
    public function functionAction(): ViewModel
    {
        return new ViewModel([
            'currentFunctions' => InstallationFunctions::getFunctionsArray($this->translator, false, false, true),
            'legacyFunctions' => InstallationFunctions::getFunctionsArray($this->translator, false, true, false),
            'adminFunctions' => InstallationFunctions::getFunctionsArray($this->translator, true, false, false),
        ]);
    }

    /**
     * Mailing list action
     */
    public function listAction(): ViewModel
    {
        if ($this->getRequest()->isPost()) {
            $this->mailingListService->addList($this->getRequest()->getPost()->toArray());
        }

        return new ViewModel([
            'lists' => $this->mailingListService->getAllLists(),
            'form' => $this->mailingListService->getListForm(),
        ]);
    }

    /**
     * List deletion action
     */
    public function deleteListAction(): HttpResponse|ViewModel
    {
        $name = $this->params()->fromRoute('name');

        if ($this->getRequest()->isPost()) {
            if ($this->mailingListService->delete($name, $this->getRequest()->getPost()->toArray())) {
                return new ViewModel([
                    'success' => true,
                    'name' => $name,
                ]);
            }

            // redirect back
            return $this->redirect()->toRoute('settings/default', ['action' => 'list']);
        }

        return new ViewModel([
            'form' => $this->mailingListService->getDeleteListForm(),
            'name' => $name,
        ]);
    }
}
