<?php

declare(strict_types=1);

namespace App\Form\Member;

use App\Entity\Member\Enums\Studies;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Grouping and labelling of the studies offered in a select.
 *
 * The grouping lives here rather than on the enum because a translator would then be needed while the form is being
 * built, which fixes the labels to whatever locale was active at that moment instead of the one the form is
 * rendered in.
 */
class StudyChoices
{
    private const string GROUP_BACHELOR = 'Bachelor Programs';
    private const string GROUP_PREMASTER = 'Pre-master Programs';
    private const string GROUP_GRADUATE = 'Master Programs';
    private const string GROUP_ENGD_PHD = 'EngD / PhD Programs';
    private const string GROUP_OTHER = 'Other';
    private const string GROUP_SPECIAL = 'Special cases (secretary use only)';

    /**
     * The keys are the optgroup labels, translated when the choice list is rendered. `Other` closes off every group
     * so that it can be picked without hunting through the whole list.
     *
     * @return array<string, array<string, string>>
     */
    public static function grouped(bool $withSpecialCases = false): array
    {
        $categories = [
            self::GROUP_BACHELOR => static fn (Studies $study): bool => $study->isBachelor(),
            self::GROUP_PREMASTER => static fn (Studies $study): bool => $study->isPreMaster(),
            self::GROUP_GRADUATE => static fn (Studies $study): bool => $study->isGraduate(),
            self::GROUP_ENGD_PHD => static fn (Studies $study): bool => $study->isEngDPhD(),
        ];

        $choices = [];

        foreach ($categories as $group => $belongsToCategory) {
            foreach (Studies::cases() as $study) {
                if (
                    !$belongsToCategory($study)
                    && Studies::Other !== $study
                ) {
                    continue;
                }

                $choices[$group][$study->name] = $study->value;
            }
        }

        if ($withSpecialCases) {
            foreach (Studies::cases() as $study) {
                if (!$study->isSpecial()) {
                    continue;
                }

                $choices[self::GROUP_SPECIAL][$study->name] = $study->value;
            }
        } else {
            $choices[self::GROUP_OTHER] = [Studies::Other->name => Studies::Other->value];
        }

        return $choices;
    }

    /**
     * Data Science studies carry a footnote marker on the registration form.
     */
    public static function label(
        string $study,
        TranslatorInterface $translator,
        bool $withDataScienceFootnote = false,
    ): string {
        $study = Studies::from($study);
        $name = $study->getName()->trans($translator);

        if (
            $withDataScienceFootnote
            && $study->isDataScience()
        ) {
            return $name . '¹';
        }

        return $name;
    }
}
