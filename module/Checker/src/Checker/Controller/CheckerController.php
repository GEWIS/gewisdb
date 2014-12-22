<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 22-12-14
 * Time: 18:08
 */
namespace Checker\Controller;

use Zend\Mvc\Controller\AbstractActionController;

class CheckerController extends AbstractActionController {

    /**
     * Index action.
     */
    public function indexAction()
    {
        \Zend\Debug\Debug::dump('test');
    }
}