<?php

declare(strict_types=1);

namespace App\Entity\Database\Enums;

use Override;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_map;
use function array_merge;

/**
 * Enum for the different address types.
 */
enum AddressTypes: string implements TranslatableInterface
{
    case Home = 'home';
    case Student = 'student';
    case Mail = 'mail';

    /**
     * The address type name, deferred so the caller decides on the locale (or takes the source string).
     */
    public function getName(): TranslatableMessage
    {
        return match ($this) {
            self::Home => new TranslatableMessage('Home Address (Parents)'),
            self::Student => new TranslatableMessage('Student Address'),
            self::Mail => new TranslatableMessage('Mail Address'),
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
     * @return array<array-key, AddressTypes|string>
     */
    public static function values(): array
    {
        return array_merge(
            array_map(
                static fn (self $status) => $status->value,
                self::cases(),
            ),
            self::cases(),
        );
    }
}
