<?php

declare(strict_types=1);

namespace App\Entity\Application;

use Laminas\Mvc\I18n\Translator;

interface FormSelectable
{
    /**
     * Returns a list of value options to be used in a form Select element.
     *
     * @return array<string, array{label: string, options: array<string, string>}>
     */
    public static function getValueOptions(Translator $translator): array;
}
