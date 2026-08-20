<?php

declare(strict_types=1);

namespace App\Entity\Database\Enums;

use Override;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Enum for the different membership types as defined in the articles of association.
 *
 * Membership types (e.g. ordinary, prolonged) <2022 have been mapped into ordinary
 */
enum MembershipTypes: string implements TranslatableInterface
{
    case Ordinary = 'ordinary';
    case External = 'external';
    case Graduate = 'graduate';
    case Honorary = 'honorary';

    /**
     * The membership type name, deferred so the caller decides on the locale (or takes the source string).
     */
    public function getName(): TranslatableMessage
    {
        return match ($this) {
            self::Ordinary => new TranslatableMessage('Ordinary'),
            self::External => new TranslatableMessage('External'),
            self::Graduate => new TranslatableMessage('Graduate'),
            self::Honorary => new TranslatableMessage('Honorary'),
        };
    }

    #[Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return $this->getName()->trans(
            $translator,
            $locale,
        );
    }

    /**
     * Whether this is a membership type that is a formal member of the association.
     */
    public function isFormalMember(): bool
    {
        return self::Graduate !== $this;
    }
}
