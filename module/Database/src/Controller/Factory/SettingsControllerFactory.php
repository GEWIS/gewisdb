<?php

declare(strict_types=1);

namespace Database\Controller\Factory;

use Database\Controller\SettingsController;
use Database\Service\InstallationFunction as InstallationFunctionService;
use Database\Service\MailingList as MailingListService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class SettingsControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): SettingsController {
        /** @var InstallationFunctionService $installationFunctionService */
        $installationFunctionService = $container->get(InstallationFunctionService::class);
        /** @var MailingListService $mailingListService */
        $mailingListService = $container->get(MailingListService::class);

        return new SettingsController(
            $installationFunctionService,
            $mailingListService,
        );
    }
}
