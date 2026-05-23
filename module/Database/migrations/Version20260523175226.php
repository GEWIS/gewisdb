<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

use function array_map;
use function implode;
use function sprintf;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260523175226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change study field to conform to the new enum values, and set non-conforming values to "Other"';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE member SET study = \'Bachelor Computer Science and Engineering\' WHERE study = \'Bachelor Web Science\' OR study = \'Bachelor Software Science\'');
        $this->addSql('UPDATE prospectivemember SET study = \'Bachelor Computer Science and Engineering\' WHERE study = \'Bachelor Web Science\' OR study = \'Bachelor Software Science\'');

        // All studies that were in module/Database/src/Model/Enums/Studies.php at the time of writing this migration
        $validStudies = [
            /** Bachelor programs */
            'Bachelor Applied Mathematics',
            'Bachelor Computer Science and Engineering',
            'Bachelor Data Science',

            /** Premaster programs */
            'Pre-master Computer Science and Engineering',
            'Pre-master Data Science and Artificial Intelligence',
            'Pre-master Embedded Systems',
            'Pre-master Industrial and Applied Mathematics',
            'Pre-master Information Security Technology',
            'Schakelprogramma SEC Leraar vho Informatica',
            'Schakelprogramma SEC Leraar vho Wiskunde',

            /** Graduate programs */
            'Master Artificial Intelligence & Engineering Systems',
            'Master Computer Science and Engineering',
            'Master Data Science & Artificial Intelligence',
            'Master Data Science in Business and Entrepreneurship',
            'Master Embedded Systems',
            'Master Industrial and Applied Mathematics',
            'Master Information Security Technology',
            'Master Science Education',

            /** EngD / PhD programs */
            'EngD Automotive Systems Design',
            'EngD Data Science',
            'EngD Mechatronic Systems Design',
            'EngD Software Technology',
            'PhD Computer Science',
            'PhD Data Science',
            'PhD Mathematics',

            /** Other */
            'Other',
        ];
        $quotedStudies = array_map(fn (string $study): string => $this->connection->quote($study), $validStudies);

        $this->addSql(sprintf(
            'UPDATE member SET study = \'Other\' WHERE study IS NOT NULL AND study NOT IN (%s)',
            implode(', ', $quotedStudies),
        ));
        $this->addSql(sprintf(
            'UPDATE prospectivemember SET study = \'Other\' WHERE study IS NOT NULL AND study NOT IN (%s)',
            implode(', ', $quotedStudies),
        ));
    }

    public function down(Schema $schema): void
    {
        // This migration is irreversible because it filters out all non-compliant data.
    }
}
