<?php

namespace Database\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class QueryController extends AbstractActionController
{
    /**
     * Index action.
     */
    public function indexAction()
    {
        $service = $this->getQueryService();

        if ($this->getRequest()->isPost()) {
            $post = $this->getRequest()->getPost();

            if (isset($post['submit_save'])) {
                $query = $service->save($post);
            }

            $result = $service->execute($post);

            if (!is_null($result) && count($result) > 0) {
                return new ViewModel(array(
                    'form' => $service->getQueryForm(),
                    'exportform' => $service->getQueryExportForm(),
                    'result' => $result,
                    'saved' => $service->getSavedQueries(),
                    'entities' => $service->getEntities()
                ));
            } elseif (isset($query)) {
                return $this->redirect()->toRoute('query/show', array(
                    'query' => $query->getId()
                ));
            }
        }

        return new ViewModel(array(
            'form' => $service->getQueryForm(),
            'saved' => $service->getSavedQueries(),
            'entities' => $service->getEntities()
        ));
    }

    /**
     * Show a saved query.
     */
    public function showAction()
    {
        $service = $this->getQueryService();

        $viewmodel = new Viewmodel(array(
            'form' => $service->getQueryForm(),
            'saved' => $service->getSavedQueries(),
            'exportform' => $service->getQueryExportForm(),
            'result' => $service->executeSaved($this->params()->fromRoute('query')),
            'entities' => $service->getEntities()
        ));

        $viewmodel->setTemplate('database/query/index');

        return $viewmodel;
    }

    /**
     * Export action.
     */
    public function exportAction()
    {
        $service = $this->getQueryService();

        if ($this->getRequest()->isPost()) {
            $result = $service->execute($this->getRequest()->getPost(), true);

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

    /**
     * Get the query service.
     *
     * @return \Database\Service\Query
     */
    public function getQueryService()
    {
        return $this->getServiceLocator()->get('database_service_query');
    }
}
