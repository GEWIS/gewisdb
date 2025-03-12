<?php

declare(strict_types=1);

namespace Database\Controller;

use Database\Service\Query as QueryService;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class QueryController extends AbstractActionController
{
    public function __construct(private readonly QueryService $queryService)
    {
    }

    /**
     * Index action.
     */
    public function indexAction(): HttpResponse|ViewModel
    {
        if ($this->getRequest()->isPost()) {
            $post = $this->getRequest()->getPost()->toArray();

            if (isset($post['submit_save'])) {
                $query = $this->queryService->save($post);
            }

            $result = $this->queryService->execute($post);

            if (isset($query)) {
                return $this->redirect()->toRoute('query/show', [
                    'query' => $query->getId(),
                ]);
            }

            if (null !== $result) {
                return new ViewModel([
                    'form' => $this->queryService->getQueryForm(),
                    'exportform' => $this->queryService->getQueryExportForm(),
                    'result' => $result,
                    'saved' => $this->queryService->getSavedQueries(),
                    'entities' => $this->queryService->getEntities(),
                    'query' => null,
                ]);
            }
        }

        return new ViewModel([
            'form' => $this->queryService->getQueryForm(),
            'saved' => $this->queryService->getSavedQueries(),
            'entities' => $this->queryService->getEntities(),
            'query' => null,
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
            'result' => $this->queryService->executeSaved((int) $this->params()->fromRoute('query')),
            'entities' => $this->queryService->getEntities(),
            'query' => $this->queryService->getSavedQuery((int) $this->params()->fromRoute('query')),
        ]);

        $viewmodel->setTemplate('database/query/index');

        return $viewmodel;
    }

    /**
     * Export action.
     */
    public function exportAction(): HttpResponse|ViewModel
    {
        if ($this->getRequest()->isPost()) {
            $result = $this->queryService->execute(
                $this->getRequest()->getPost()->toArray(),
                true,
            );

            $name = $this->getRequest()->getPost()->toArray()['name'];
            if (empty($name)) {
                $name = 'query';
            }

            if (null !== $result) {
                $vm = new ViewModel([
                    'result' => $result,
                    'name' => $name,
                ]);

                $vm->setTemplate('database/query/export');
                $vm->setTerminal(true);

                return $vm;
            }
        }

        return $this->redirect()->toRoute('query');
    }
}
