<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20250621133117 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Introduces support for boolean configuration items';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE configitem ADD valueBool BOOLEAN DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ConfigItem DROP valueBool');
    }
}
