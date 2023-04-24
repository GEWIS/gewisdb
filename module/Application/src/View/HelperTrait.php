<?php

declare(strict_types=1);

namespace Application\View;

use Laminas\Form\View\HelperTrait as FormHelperTrait;
use Laminas\I18n\View\HelperTrait as I18nHelperTrait;
use Laminas\Mvc\Plugin\FlashMessenger\View\HelperTrait as FlashMessengerHelperTrait;

/**
 * Helper trait for auto-completion of code in modern IDEs.
 *
 * The trait provides convenience methods for view helpers, defined in the application module. It is designed to be used
 * for type-hinting $this variable inside laminas-view templates via doc blocks.
 *
 * Other traits from laminas are already chained into this trait. This includes support for the FlashMessenger, Form,
 * and i18n view helpers.
 *
 * @method string fileUrl(string $path)
 * @method bool moduleIsActive(array $condition)
 */
trait HelperTrait
{
    use FlashMessengerHelperTrait;
    use FormHelperTrait;
    use I18nHelperTrait;
}
