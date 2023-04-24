<?php

declare(strict_types=1);

namespace Application\Model\Enums;

/**
 * Enum for the different address types.
 */
enum MeetingTypes: string
{
    case BV = 'BV'; // bestuursvergadering
    case ALV = 'ALV'; // algemene leden vergadering
    case VV = 'VV'; // voorzitters vergadering
    case VIRT = 'Virt'; // virtual meeting

    public static function values(): array
    {
        return array_merge(
            array_map(
                fn (self $status) => $status->value,
                self::cases(),
            ),
            self::cases(),
        );
    }
}
