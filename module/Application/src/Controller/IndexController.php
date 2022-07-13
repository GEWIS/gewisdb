<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container as SessionContainer;

class IndexController extends AbstractActionController
{
    public function langAction()
    {
        $session = new SessionContainer('lang');
        $session->lang = $this->params()->fromRoute('lang');

        if ($session->lang != 'en' && $session->lang != 'nl') {
            $session->lang = 'nl';
        }

        return $this->redirect()->toRoute('home');
    }
}
