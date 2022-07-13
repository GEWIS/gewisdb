<?php

namespace Database\Controller;

use Database\Service\Query as QueryService;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class QueryController extends AbstractActionController
{
    /** @var QueryService $queryService */
    private $queryService;

    /**
     * @param QueryService $queryService
     */
    public function __construct(QueryService $queryService)
    {
        $this->queryService = $queryService;
    }

    /**
     * Index action.
     */
    public function indexAction()
    {
        if ($this->getRequest()->isPost()) {
            $post = $this->getRequest()->getPost();

            if (isset($post['submit_save'])) {
                $query = $this->queryService->save($post);
            }

            $result = $this->queryService->execute($post);

            if (!is_null($result) && count($result) > 0) {
                return new ViewModel(array(
                    'form' => $this->queryService->getQueryForm(),
                    'exportform' => $this->queryService->getQueryExportForm(),
                    'result' => $result,
                    'saved' => $this->queryService->getSavedQueries(),
                    'entities' => $this->queryService->getEntities()
                ));
            } elseif (isset($query)) {
                return $this->redirect()->toRoute('query/show', array(
                    'query' => $query->getId()
                ));
            }
        }

        return new ViewModel(array(
            'form' => $this->queryService->getQueryForm(),
            'saved' => $this->queryService->getSavedQueries(),
            'entities' => $this->queryService->getEntities()
        ));
    }

    /**
     * Show a saved query.
     */
    public function showAction()
    {
        $viewmodel = new Viewmodel(array(
            'form' => $this->queryService->getQueryForm(),
            'saved' => $this->queryService->getSavedQueries(),
            'exportform' => $this->queryService->getQueryExportForm(),
            'result' => $this->queryService->executeSaved($this->params()->fromRoute('query')),
            'entities' => $this->queryService->getEntities()
        ));

        $viewmodel->setTemplate('database/query/index');

        return $viewmodel;
    }

    /**
     * Export action.
     */
    public function exportAction()
    {
        if ($this->getRequest()->isPost()) {
            $result = $this->queryService->execute($this->getRequest()->getPost(), true);

            if (null !== $result) {
                $vm = new ViewModel(array(
                    'result' => $result
                ));

                $vm->setTemplate('database/query/export');
                $vm->setTerminal(true);

                return $vm;
            }
        }
        return $this->redirect()->toRoute('query');
    }
}
