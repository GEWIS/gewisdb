<?php

declare(strict_types=1);

namespace Database\Model\Enums;

use Application\Model\Enums\AppLanguages;
use Laminas\Mvc\I18n\DummyTranslator;
use Laminas\Mvc\I18n\Translator;

use function array_combine;
use function array_filter;
use function array_map;
use function in_array;

/**
 * Enum with studies
 */
enum Studies: string
{
    /** Bachelor programs */
    case BAM = 'Bachelor Applied Mathematics';
    case BCS = 'Bachelor Computer Science and Engineering';
    case BDS = 'Bachelor Data Science';

    /** Premaster programs */
    case PMCSE = 'Pre-master Computer Science and Engineering';
    case PMDSAI = 'Pre-master Data Science and Artificial Intelligence';
    case PMES = 'Pre-master Embedded Systems';
    case PMIAM = 'Pre-master Industrial and Applied Mathematics';
    case PMIST = 'Pre-master Information Security Technology';
    case SkSEInf = 'Schakelprogramma SEC Leraar vho Informatica';
    case SkSEWisk = 'Schakelprogramma SEC Leraar vho Wiskunde';

    /** Graduate programs */
    case MAIES = 'Master Artificial Intelligence & Engineering Systems';
    case MCSE = 'Master Computer Science and Engineering';
    case MDSAI = 'Master Data Science & Artificial Intelligence';
    case MDSBE = 'Master Data Science in Business and Entrepreneurship';
    case MES = 'Master Embedded Systems';
    case MIAM = 'Master Industrial and Applied Mathematics';
    case MIST = 'Master Information Security Technology';
    case MSEC = 'Master Science Education';

    /** EngD / PhD programs */
    case EngDASD = 'EngD Automotive Systems Design';
    case EngDDS = 'EngD Data Science';
    case EngDMSD = 'EngD Mechatronic Systems Design';
    case EngDST = 'EngD Software Technology';
    case PhDCS = 'PhD Computer Science';
    case PhDDS = 'PhD Data Science';
    case PhDM = 'PhD Mathematics';

    /** Other */
    case Other = 'Other';

    public function isBachelor(): bool
    {
        return in_array($this, [
            self::BAM,
            self::BCS,
            self::BDS,
        ]);
    }

    public function isPreMaster(): bool
    {
        return in_array($this, [
            self::PMCSE,
            self::PMDSAI,
            self::PMES,
            self::PMIAM,
            self::PMIST,
            self::SkSEInf,
            self::SkSEWisk,
        ]);
    }

    public function isGraduate(): bool
    {
        return in_array($this, [
            self::MAIES,
            self::MCSE,
            self::MDSAI,
            self::MDSBE,
            self::MES,
            self::MIAM,
            self::MIST,
            self::MSEC,
        ]);
    }

    public function isEngDPhD(): bool
    {
        return in_array($this, [
            self::EngDASD,
            self::EngDDS,
            self::EngDMSD,
            self::EngDST,
            self::PhDCS,
            self::PhDDS,
            self::PhDM,
        ]);
    }

    public function isDataScience(): bool
    {
        return in_array($this, [
            self::BDS,
            self::PMDSAI,
            self::MDSAI,
            self::EngDDS,
            self::PhDDS,
        ]);
    }

    /**
     * Give the function name with the given translation. If no translator is given, we return the default language.
     */
    public function getName(
        ?Translator $translator,
        ?AppLanguages $language = null,
    ): string {
        if (null === $translator) {
            $translator = new DummyTranslator();
        }

        return match ($this) {
            self::BAM => $translator->translate(
                'Bachelor Applied Mathematics',
                locale: $language?->getLangParam(),
            ),
            self::BCS => $translator->translate(
                'Bachelor Computer Science and Engineering',
                locale: $language?->getLangParam(),
            ),
            self::BDS => $translator->translate(
                'Bachelor Data Science',
                locale: $language?->getLangParam(),
            ),
            self::PMCSE => $translator->translate(
                'Pre-master Computer Science and Engineering',
                locale: $language?->getLangParam(),
            ),
            self::PMDSAI => $translator->translate(
                'Pre-master Data Science and Artificial Intelligence',
                locale: $language?->getLangParam(),
            ),
            self::PMES => $translator->translate(
                'Pre-master Embedded Systems',
                locale: $language?->getLangParam(),
            ),
            self::PMIAM => $translator->translate(
                'Pre-master Industrial and Applied Mathematics',
                locale: $language?->getLangParam(),
            ),
            self::PMIST => $translator->translate(
                'Pre-master Information Security Technology',
                locale: $language?->getLangParam(),
            ),
            self::SkSEInf => $translator->translate(
                'Schakelprogramma SEC Leraar vho Informatica',
                locale: $language?->getLangParam(),
            ),
            self::SkSEWisk => $translator->translate(
                'Schakelprogramma SEC Leraar vho Wiskunde',
                locale: $language?->getLangParam(),
            ),
            self::MAIES => $translator->translate(
                'Master Artificial Intelligence & Engineering Systems',
                locale: $language?->getLangParam(),
            ),
            self::MCSE => $translator->translate(
                'Master Computer Science and Engineering',
                locale: $language?->getLangParam(),
            ),
            self::MDSAI => $translator->translate(
                'Master Data Science & Artificial Intelligence',
                locale: $language?->getLangParam(),
            ),
            self::MDSBE => $translator->translate(
                'Master Data Science in Business and Entrepreneurship',
                locale: $language?->getLangParam(),
            ),
            self::MES => $translator->translate(
                'Master Embedded Systems',
                locale: $language?->getLangParam(),
            ),
            self::MIAM => $translator->translate(
                'Master Industrial and Applied Mathematics',
                locale: $language?->getLangParam(),
            ),
            self::MIST => $translator->translate(
                'Master Information Security Technology',
                locale: $language?->getLangParam(),
            ),
            self::MSEC => $translator->translate(
                'Master Science Education',
                locale: $language?->getLangParam(),
            ),
            self::EngDASD => $translator->translate(
                'EngD Automotive Systems Design',
                locale: $language?->getLangParam(),
            ),
            self::EngDDS => $translator->translate(
                'EngD Data Science',
                locale: $language?->getLangParam(),
            ),
            self::EngDMSD => $translator->translate(
                'EngD Mechatronic Systems Design',
                locale: $language?->getLangParam(),
            ),
            self::EngDST => $translator->translate(
                'EngD Software Technology',
                locale: $language?->getLangParam(),
            ),
            self::PhDCS => $translator->translate(
                'PhD Computer Science',
                locale: $language?->getLangParam(),
            ),
            self::PhDDS => $translator->translate(
                'PhD Data Science',
                locale: $language?->getLangParam(),
            ),
            self::PhDM => $translator->translate(
                'PhD Mathematics',
                locale: $language?->getLangParam(),
            ),
            self::Other => $translator->translate(
                'Other',
                locale: $language?->getLangParam(),
            ),
        };
    }

    private static function getCategoryName(
        string $category,
        ?Translator $translator,
        ?AppLanguages $language = null,
    ): string {
        if (null === $translator) {
            $translator = new DummyTranslator();
        }

        return match ($category) {
            'bachelor' => $translator->translate(
                'Bachelor Programs',
                locale: $language?->getLangParam(),
            ),
            'premaster' => $translator->translate(
                'Pre-master Programs',
                locale: $language?->getLangParam(),
            ),
            'graduate' => $translator->translate(
                'Master Programs',
                locale: $language?->getLangParam(),
            ),
            'phd' => $translator->translate(
                'EngD / PhD Programs',
                locale: $language?->getLangParam(),
            ),
            'other' => $translator->translate(
                'Other',
                locale: $language?->getLangParam(),
            ),
        };
    }

    /**
     * Returns a list of categorised studies
     *
     * @return array<string, array{label: string, options: array<string, string>}>
     */
    public static function getFunctionsArray(
        Translator $translator,
        bool $withDSFootnote = false,
    ): array {
        $result = [];

        foreach (self::getCategorisedStudies() as $category => $studies) {
            $result[$category] = [
                'label' => self::getCategoryName($category, $translator),
                'options' => array_combine(
                    array_map(static function ($func) {
                        return $func->value;
                    }, $studies),
                    array_map(static function ($func) use ($translator, $withDSFootnote) {
                        return $withDSFootnote && $func->isDataScience()
                            ? $func->getName($translator) . '¹'
                            : $func->getName($translator);
                    }, $studies),
                ),
            ];
        }

        return $result;
    }

    /**
     * Returns a list of categorised studies
     *
     * @return array<string, array<Studies>>
     */
    private static function getCategorisedStudies(): array
    {
        return [
            'bachelor' => array_filter(self::cases(), static function ($case) {
                return $case->isBachelor() || self::Other === $case;
            }),
            'premaster' => array_filter(self::cases(), static function ($case) {
                return $case->isPreMaster() || self::Other === $case;
            }),
            'graduate' => array_filter(self::cases(), static function ($case) {
                return $case->isGraduate() || self::Other === $case;
            }),
            'phd' => array_filter(self::cases(), static function ($case) {
                return $case->isEngDPhD() || self::Other === $case;
            }),
            'other' => [self::Other],
        ];
    }
}
