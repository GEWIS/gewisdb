<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 */
final class Version20250126144021 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Introduce query categories';
    }

    public function up(Schema $schema): void
    {
        // phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
        $this->addSql('ALTER TABLE savedquery ADD category VARCHAR(255)');
        $this->addSql('UPDATE savedquery SET category = trim(split_part(name, \':\', 1)), name = trim(split_part(name, \':\', -1))');
        $this->addSql('ALTER TABLE savedquery ALTER COLUMN category SET NOT NULL');
        // phpcs:enable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
    }

    public function down(Schema $schema): void
    {
        // phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
        $this->addSql('UPDATE savedquery SET name = concat(category, \': \', name)');
        $this->addSql('ALTER TABLE SavedQuery DROP category');
        // phpcs:enable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
    }
}
