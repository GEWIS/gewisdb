<?php

declare(strict_types=1);

namespace App\ViewModel\Database;

use App\Entity\Database\AuditEntry;
use DateTimeInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_map;
use function htmlspecialchars;
use function vsprintf;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

/**
 * One row of a member's audit trail, with its body already safe to print unescaped.
 *
 * An audit entry is a translatable format string owned by the entity, filled with arguments that are not: an
 * AuditNote's arguments include the free text a secretary typed. The format string carries markup and so has to
 * reach the template unescaped, which means the arguments must be escaped before they are interpolated — doing it
 * the other way round escapes the markup, and doing neither lets note text inject into the page.
 */
final readonly class AuditEntryRow
{
    private function __construct(
        public DateTimeInterface $updatedAt,
        public string $body,
        public string $user,
    ) {
    }

    public static function fromEntry(
        AuditEntry $entry,
        TranslatorInterface $translator,
    ): self {
        $string = $entry->getStringPlain();

        $arguments = array_map(
            static fn (string $argument): string => htmlspecialchars(
                $argument,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8',
            ),
            $string['arguments'],
        );

        return new self(
            $entry->getUpdatedAt(),
            vsprintf(
                $translator->trans($string['bodyFormatted']),
                $arguments,
            ),
            $entry->getUserName(),
        );
    }
}
