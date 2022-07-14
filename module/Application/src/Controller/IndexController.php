<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Session\Container as SessionContainer;

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
