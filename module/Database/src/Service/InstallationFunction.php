<?php

declare(strict_types=1);

namespace Database\Service;

use Database\Form\InstallationFunction as InstallationFunctionForm;
use Database\Mapper\InstallationFunction as InstallationFunctionMapper;
use Database\Model\InstallationFunction as InstallationFunctionModel;

class InstallationFunction
{
    public function __construct(
        private readonly InstallationFunctionForm $installationFunctionForm,
        private readonly InstallationFunctionMapper $installationFunctionMapper,
    ) {
    }

    /**
     * Get all functions.
     *
     * @return InstallationFunctionModel[]
     */
    public function getAllFunctions(): array
    {
        return $this->getFunctionMapper()->findAll();
    }

    /**
     * Add a function.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function addFunction(array $data): bool
    {
        $form = $this->getFunctionForm();

        $form->setData($data);
        $form->bind(new InstallationFunctionModel());

        if (!$form->isValid()) {
            return false;
        }

        /** @var InstallationFunctionModel $function */
        $function = $form->getData();
        $this->getFunctionMapper()->persist($function);

        return true;
    }

    /**
     * Get the function form.
     */
    public function getFunctionForm(): InstallationFunctionForm
    {
        return $this->installationFunctionForm;
    }

    /**
     * Get the installation function mapper.
     */
    public function getFunctionMapper(): InstallationFunctionMapper
    {
        return $this->installationFunctionMapper;
    }
}
