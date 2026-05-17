<?php

declare(strict_types=1);

namespace Database\Controller\Factory;

use Database\Controller\SettingsController;
use Database\Service\MailingList as MailingListService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class SettingsControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): SettingsController {
        $translator = $container->get(MvcTranslator::class);
        $mailingListService = $container->get(MailingListService::class);

        return new SettingsController(
            $translator,
            $mailingListService,
        );
    }
}
