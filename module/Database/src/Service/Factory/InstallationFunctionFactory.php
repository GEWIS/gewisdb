<?php

declare(strict_types=1);

namespace Database\Service\Factory;

use Database\Form\InstallationFunction as InstallationFunctionForm;
use Database\Mapper\InstallationFunction as InstallationFunctionMapper;
use Database\Service\InstallationFunction as InstallationFunctionService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class InstallationFunctionFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): InstallationFunctionService {
        /** @var InstallationFunctionForm $installationFunctionForm */
        $installationFunctionForm = $container->get(InstallationFunctionForm::class);
        /** @var InstallationFunctionMapper $installationFunctionMapper */
        $installationFunctionMapper = $container->get(InstallationFunctionMapper::class);

        return new InstallationFunctionService(
            $installationFunctionForm,
            $installationFunctionMapper,
        );
    }
}
