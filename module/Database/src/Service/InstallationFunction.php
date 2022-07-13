<?php

namespace Database\Service;

use Application\Service\AbstractService;
use Database\Model\InstallationFunction as FunctionModel;

class InstallationFunction extends AbstractService
{
    /**
     * Get all functions.
     *
     * @return array of InstallationFunction's
     */
    public function getAllFunctions()
    {
        return $this->getFunctionMapper()->findAll();
    }

    /**
     * Add a function.
     *
     * @param $data POST data.
     *
     * @return boolean if succeeded
     */
    public function addFunction($data)
    {
        $form = $this->getFunctionForm();

        $form->setData($data);
        $form->bind(new FunctionModel());

        if (!$form->isValid()) {
            return false;
        }

        $function = $form->getData();

        $this->getEventManager()->trigger(__FUNCTION__ . '.pre', array('function' => $function));
        $this->getFunctionMapper()->persist($function);
        $this->getEventManager()->trigger(__FUNCTION__ . '.post', array('function' => $function));

        return true;
    }

    /**
     * Get the function form.
     *
     * @return \Database\Form\InstallationFunction
     */
    public function getFunctionForm()
    {
        return $this->getServiceManager()->get('database_form_installationfunction');
    }

    /**
     * Get the installation function mapper.
     *
     * @return \Database\Mapper\InstallationFunction
     */
    public function getFunctionMapper()
    {
        return $this->getServiceManager()->get('database_mapper_installationfunction');
    }
}
