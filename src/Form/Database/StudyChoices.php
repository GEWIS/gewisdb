<?php

declare(strict_types=1);

namespace App\Form\Database;

use App\Entity\Database\Enums\Studies;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Grouping and labelling of the studies offered in a select.
 *
 * `EnumType` cannot group its options, which is the one reason the study select is still built by hand: the choices
 * are the enum cases themselves, so they label and value themselves the way `EnumType` would have done, but they are
 * handed over in optgroups. The footnote below is the second reason, and the only place a study is labelled here.
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
     * The outer keys are the optgroup labels, translated when the choice list is rendered. `Other` closes off every
     * group so that it can be picked without hunting through the whole list.
     *
     * @return array<string, array<string, Studies>>
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

                $choices[$group][$study->name] = $study;
            }
        }

        if ($withSpecialCases) {
            foreach (Studies::cases() as $study) {
                if (!$study->isSpecial()) {
                    continue;
                }

                $choices[self::GROUP_SPECIAL][$study->name] = $study;
            }
        } else {
            $choices[self::GROUP_OTHER] = [Studies::Other->name => Studies::Other];
        }

        return $choices;
    }

    /**
     * Data Science studies carry a footnote marker on the registration form. Appending it means translating the name
     * first, so only those labels are resolved here; the rest is left to the enum, which the choice list translates
     * when it renders.
     */
    public static function labelWithFootnote(
        Studies $study,
        TranslatorInterface $translator,
    ): Studies|string {
        if (!$study->isDataScience()) {
            return $study;
        }

        return $study->getName()->trans($translator) . '¹';
    }
}
