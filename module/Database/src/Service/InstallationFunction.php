<?php

namespace Database\Service;

use Database\Form\InstallationFunction as InstallationFunctionForm;
use Database\Mapper\InstallationFunction as InstallationFunctionMapper;
use Database\Model\InstallationFunction as FunctionModel;

class InstallationFunction
{
    /** @var InstallationFunctionForm $installationFunctionForm */
    private $installationFunctionForm;

    /** @var InstallationFunctionMapper $installationFunctionMapper */
    private $installationFunctionMapper;

    /**
     * @param InstallationFunctionForm $installationFunctionForm
     * @param InstallationFunctionMapper $installationFunctionMapper
     */
    public function __construct(
        InstallationFunctionForm $installationFunctionForm,
        InstallationFunctionMapper $installationFunctionMapper
    ) {
        $this->installationFunctionForm = $installationFunctionForm;
        $this->installationFunctionMapper = $installationFunctionMapper;
    }

    /**
     * Get all functions.
     *
     * @return array of InstallationFunction's
     */
    public function getAllFunctions(): array
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
    public function addFunction($data): bool
    {
        $form = $this->getFunctionForm();

        $form->setData($data);
        $form->bind(new FunctionModel());

        if (!$form->isValid()) {
            return false;
        }

        $function = $form->getData();

        // TODO: Fix global event listener.
        // $this->getEventManager()->trigger(__FUNCTION__ . '.pre', array('function' => $function));
        $this->getFunctionMapper()->persist($function);
        // TODO: Fix global event listener.
        // $this->getEventManager()->trigger(__FUNCTION__ . '.post', array('function' => $function));

        return true;
    }

    /**
     * Get the function form.
     *
     * @return InstallationFunctionForm
     */
    public function getFunctionForm(): InstallationFunctionForm
    {
        return $this->installationFunctionForm;
    }

    /**
     * Get the installation function mapper.
     *
     * @return InstallationFunctionMapper
     */
    public function getFunctionMapper(): InstallationFunctionMapper
    {
        return $this->installationFunctionMapper;
    }
}
