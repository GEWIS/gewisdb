<?php

declare(strict_types=1);

namespace Database\Form;

use Laminas\Mvc\I18n\Translator;

/**
 * 2026-05: Removed tueData from approve form. Class kept to allow for manual process implementation.
 */
class MemberApprove extends MemberType
{
    public function __construct(private readonly Translator $translator)
    {
        parent::__construct($this->translator);
    }
}
