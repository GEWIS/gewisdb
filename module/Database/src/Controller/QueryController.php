<?php

namespace Database\Controller;

use Database\Service\Query as QueryService;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class QueryController extends AbstractActionController
{
    public function __construct(private readonly QueryService $queryService)
    {
    }

    /**
     * Index action.
     *
     * @return (Response|ViewModel)
     */
    public function indexAction(): Response|ViewModel
    {
        if ($this->getRequest()->isPost()) {
            $post = $this->getRequest()->getPost()->toArray();

            if (isset($post['submit_save'])) {
                $query = $this->queryService->save($post);
            }

            $result = $this->queryService->execute($post);

            if (!is_null($result)) {
                return new ViewModel([
                    'form' => $this->queryService->getQueryForm(),
                    'exportform' => $this->queryService->getQueryExportForm(),
                    'result' => $result,
                    'saved' => $this->queryService->getSavedQueries(),
                    'entities' => $this->queryService->getEntities(),
                ]);
            } elseif (isset($query)) {
                return $this->redirect()->toRoute('query/show', [
                    'query' => $query->getId(),
                ]);
            }
        }

        return new ViewModel([
            'form' => $this->queryService->getQueryForm(),
            'saved' => $this->queryService->getSavedQueries(),
            'entities' => $this->queryService->getEntities(),
        ]);
    }

    /**
     * Show a saved query.
     */
    public function showAction(): ViewModel
    {
        $viewmodel = new Viewmodel([
            'form' => $this->queryService->getQueryForm(),
            'saved' => $this->queryService->getSavedQueries(),
            'exportform' => $this->queryService->getQueryExportForm(),
            'result' => $this->queryService->executeSaved($this->params()->fromRoute('query')),
            'entities' => $this->queryService->getEntities(),
        ]);

        $viewmodel->setTemplate('database/query/index');

        return $viewmodel;
    }

    /**
     * Export action.
     */
    public function exportAction(): Response|ViewModel
    {
        if ($this->getRequest()->isPost()) {
            $result = $this->queryService->execute(
                $this->getRequest()->getPost()->toArray(),
                true,
            );

            if (null !== $result) {
                $vm = new ViewModel([
                    'result' => $result,
                ]);

                $vm->setTemplate('database/query/export');
                $vm->setTerminal(true);

                return $vm;
            }
        }

        return $this->redirect()->toRoute('query');
    }
}
