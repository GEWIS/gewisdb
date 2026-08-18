<?php

declare(strict_types=1);

namespace App\Form\Database;

use App\Entity\Database\Enums\MembershipTypes;
use Symfony\Component\Translation\TranslatableMessage;

use function Symfony\Component\Translation\t;

/**
 * The membership type radios spell out who each type applies to, which is more than `MembershipTypes::getName()`
 * returns.
 */
class MembershipTypeChoices
{
    public static function label(MembershipTypes $type): TranslatableMessage
    {
        return match ($type) {
            MembershipTypes::Ordinary => t('Ordinary - Enrolled at the department of M&CS'),
            MembershipTypes::External => t('External - Admitted by the board'),
            MembershipTypes::Graduate => t('Graduate - Former member admitted by the board as graduate'),
            MembershipTypes::Honorary => t('Honorary - Appointed by the GMM'),
        };
    }
}
