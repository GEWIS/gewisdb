<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260817113219 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Collect TU/e student numbers instead of TU/e usernames';
    }

    public function up(Schema $schema): void
    {
        // The stored values are already student numbers, so the columns only have to be renamed.
        $this->addSql('ALTER TABLE Member RENAME COLUMN tueUsername TO studentNumber');
        $this->addSql('ALTER TABLE ProspectiveMember RENAME COLUMN tueUsername TO studentNumber');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Member RENAME COLUMN studentNumber TO tueUsername');
        $this->addSql('ALTER TABLE ProspectiveMember RENAME COLUMN studentNumber TO tueUsername');
    }
}
