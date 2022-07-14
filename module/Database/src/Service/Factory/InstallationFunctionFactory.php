<?php

namespace Database\Service\Factory;

use Database\Form\InstallationFunction as InstallationFunctionForm;
use Database\Mapper\InstallationFunction as InstallationFunctionMapper;
use Database\Service\InstallationFunction as InstallationFunctionService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class InstallationFunctionFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return InstallationFunctionService
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): InstallationFunctionService {
        /** @var InstallationFunctionForm $installationFunctionForm */
        $installationFunctionForm = $container->get(InstallationFunctionForm::class);
        /** @var InstallationFunctionMapper $installationFunctionMapper */
        $installationFunctionMapper = $container->get(InstallationFunctionMapper::class);

        return new InstallationFunctionService(
            $installationFunctionForm,
            $installationFunctionMapper
        );
    }
}
