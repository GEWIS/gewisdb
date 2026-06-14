<?php

declare(strict_types=1);

namespace Application\Model\Enums;

use Laminas\Mvc\I18n\Translator;

/**
 * Enum for the different membership types as defined in the articles of association.
 *
 * Membership types (e.g. ordinary, prolonged) <2022 have been mapped into ordinary
 */
enum MembershipTypes: string
{
    case Ordinary = 'ordinary';
    case External = 'external';
    case Graduate = 'graduate';
    case Honorary = 'honorary';

    public function getName(Translator $translator): string
    {
        return match ($this) {
            self::Ordinary => $translator->translate('Ordinary'),
            self::External => $translator->translate('External'),
            self::Graduate => $translator->translate('Graduate'),
            self::Honorary => $translator->translate('Honorary'),
        };
    }

    /**
     * Whether this is a membership type that is a formal member of the association.
     */
    public function isFormalMember(): bool
    {
        return self::Graduate !== $this;
    }
}
