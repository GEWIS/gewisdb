<?php

declare(strict_types=1);

namespace Database\Controller;

use Database\Model\Enums\InstallationFunctions;
use Database\Model\MailingList as MailingListModel;
use Database\Service\MailingList as MailingListService;
use Laminas\Http\Request;
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
    public function listsAction(): ViewModel
    {
        return new ViewModel([
            'lists' => $this->mailingListService->getAllLists(),
            'mailman' => $this->mailingListService->getMailmanService()->getMailingListIds(),
        ]);
    }

    public function addListAction(): HttpResponse|ViewModel
    {
        $form = $this->mailingListService->getListForm();
        $form->setMailmanIds($this->mailingListService->getMailmanService()->getMailingListIds()['lists']);

        /** @var Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->bind(new MailingListModel());
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                /** @var MailingListModel $list */
                $list = $form->getData();
                $this->mailingListService->addList($list);

                return $this->redirect()->toRoute('settings/lists/edit', ['name' => $list->getName()]);
            }
        }

        return new ViewModel([
            'form' => $form,
            'action' => 'add',
        ]);
    }

    public function editListAction(): HttpResponse|ViewModel
    {
        $listName = $this->params()->fromRoute('name');
        $list = $this->mailingListService->getList($listName);

        if (null === $list) {
            return $this->notFoundAction();
        }

        $form = $this->mailingListService->getListForm();
        $form->setMailmanIds($this->mailingListService->getMailmanService()->getMailingListIds()['lists']);

        /** @var Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                $list = $this->mailingListService->editList($list, $form->getData());

                return $this->redirect()->toRoute('settings/lists/edit', ['name' => $list->getName()]);
            }
        }

        $form->setData($list->toArray());

        return new ViewModel([
            'action' => 'edit',
            'form' => $form,
            'list' => $list,
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

    /**
     * Sync known mailing list ids from Mailman
     */
    public function syncListsAction(): HttpResponse
    {
        $this->mailingListService->getMailmanService()->cacheMailingLists();

        return $this->redirect()->toRoute('settings/lists');
    }
}
