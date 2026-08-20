<?php

declare(strict_types=1);

namespace App\Entity\Database\Enums;

use InvalidArgumentException;
use Override;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function in_array;

/**
 * Enum with studies
 *
 * This enum does not have a notion of 'deprecated' studies because, in contrast to the board functions,
 * it should be a point-in-time representation of the studies that are offered at M&CS and not a historic
 * representation.
 * If a study is no longer offered, it should be removed from this enum, and any members should be migrated
 * to the new program. Removal of a variant should be done only when the study is no longer offered, even to
 * existing students: joining GEWIS is not exclusive to freshmen, so you may encounter students who follow a
 * study that is no longer offered to new students.
 *
 * "Opleidingscode" in Centraal Register Opleidingen Hoger Onderwijs (CROHO) added. This is also the source
 * of the translation in the language files.
 */
enum Studies: string implements TranslatableInterface
{
    /** Bachelor programs */
    case BAM = 'Bachelor Applied Mathematics'; //1015O6295
    case BCS = 'Bachelor Computer Science and Engineering'; //1015O6287
    case BDS = 'Bachelor Data Science'; //1015O6359

    /** Premaster programs */
    case PMCSE = 'Pre-master Computer Science and Engineering'; //1015O6290
    case PMDSAI = 'Pre-master Data Science and Artificial Intelligence'; //1015O6291
    case PMES = 'Pre-master Embedded Systems'; //1015O6292
    case PMIAM = 'Pre-master Industrial and Applied Mathematics'; //1015O6296
    case PMIST = 'Pre-master Information Security Technology'; //1015O6293
    case SkSEInf = 'Schakelprogramma SEC Leraar vho Informatica'; //1015O6294
    case SkSEWisk = 'Schakelprogramma SEC Leraar vho Wiskunde'; //1015O6297

    /** Graduate programs */
    case MAIES = 'Master Artificial Intelligence & Engineering Systems'; //66476
    case MCSE = 'Master Computer Science and Engineering'; //1015O6321
    case MDSAI = 'Master Data Science & Artificial Intelligence'; //60976
    case MDSBE = 'Master Data Science in Business and Entrepreneurship'; //65018
    case MES = 'Master Embedded Systems'; //60331
    case MIAM = 'Master Industrial and Applied Mathematics'; //1015O6311
    case MIST = 'Master Information Security Technology'; //1015O6323
    case MSEC = 'Master Science Education'; //69345

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

    /** Special cases */
    case None = 'Not studying';
    case Unknown = 'Unknown';

    public function isBachelor(): bool
    {
        return in_array(
            $this,
            [
                self::BAM,
                self::BCS,
                self::BDS,
            ],
        );
    }

    public function isPreMaster(): bool
    {
        return in_array(
            $this,
            [
                self::PMCSE,
                self::PMDSAI,
                self::PMES,
                self::PMIAM,
                self::PMIST,
                self::SkSEInf,
                self::SkSEWisk,
            ],
        );
    }

    public function isGraduate(): bool
    {
        return in_array(
            $this,
            [
                self::MAIES,
                self::MCSE,
                self::MDSAI,
                self::MDSBE,
                self::MES,
                self::MIAM,
                self::MIST,
                self::MSEC,
            ],
        );
    }

    public function isEngDPhD(): bool
    {
        return in_array(
            $this,
            [
                self::EngDASD,
                self::EngDDS,
                self::EngDMSD,
                self::EngDST,
                self::PhDCS,
                self::PhDDS,
                self::PhDM,
            ],
        );
    }

    public function isMcsStudy(): bool
    {
        return !$this->isSpecial() && !$this->isEngDPhD();
    }

    public function isDataScience(): bool
    {
        return in_array(
            $this,
            [
                self::BDS,
                self::PMDSAI,
                self::MDSAI,
                self::EngDDS,
                self::PhDDS,
            ],
        );
    }

    public function isSpecial(): bool
    {
        return in_array(
            $this,
            [
                self::Other,
                self::None,
                self::Unknown,
            ],
        );
    }

    /**
     * The study name, deferred so the caller decides on the locale (or takes the source string).
     */
    public function getName(): TranslatableMessage
    {
        return match ($this) {
            self::BAM => new TranslatableMessage('Bachelor Applied Mathematics'),
            self::BCS => new TranslatableMessage('Bachelor Computer Science and Engineering'),
            self::BDS => new TranslatableMessage('Bachelor Data Science'),
            self::PMCSE => new TranslatableMessage('Pre-master Computer Science and Engineering'),
            self::PMDSAI => new TranslatableMessage('Pre-master Data Science and Artificial Intelligence'),
            self::PMES => new TranslatableMessage('Pre-master Embedded Systems'),
            self::PMIAM => new TranslatableMessage('Pre-master Industrial and Applied Mathematics'),
            self::PMIST => new TranslatableMessage('Pre-master Information Security Technology'),
            self::SkSEInf => new TranslatableMessage('Schakelprogramma SEC Leraar vho Informatica'),
            self::SkSEWisk => new TranslatableMessage('Schakelprogramma SEC Leraar vho Wiskunde'),
            self::MAIES => new TranslatableMessage('Master Artificial Intelligence & Engineering Systems'),
            self::MCSE => new TranslatableMessage('Master Computer Science and Engineering'),
            self::MDSAI => new TranslatableMessage('Master Data Science & Artificial Intelligence'),
            self::MDSBE => new TranslatableMessage('Master Data Science in Business and Entrepreneurship'),
            self::MES => new TranslatableMessage('Master Embedded Systems'),
            self::MIAM => new TranslatableMessage('Master Industrial and Applied Mathematics'),
            self::MIST => new TranslatableMessage('Master Information Security Technology'),
            self::MSEC => new TranslatableMessage('Master Science Education'),
            self::EngDASD => new TranslatableMessage('EngD Automotive Systems Design'),
            self::EngDDS => new TranslatableMessage('EngD Data Science'),
            self::EngDMSD => new TranslatableMessage('EngD Mechatronic Systems Design'),
            self::EngDST => new TranslatableMessage('EngD Software Technology'),
            self::PhDCS => new TranslatableMessage('PhD Computer Science'),
            self::PhDDS => new TranslatableMessage('PhD Data Science'),
            self::PhDM => new TranslatableMessage('PhD Mathematics'),
            self::Other => new TranslatableMessage('Other'),
            self::Unknown => new TranslatableMessage('Unknown'),
            self::None => new TranslatableMessage('Not studying'),
            default => throw new InvalidArgumentException('Invalid study: ' . $this->value),
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
}
