<?php

declare(strict_types=1);

namespace Report\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 */
final class Version20241124183712 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove organ function configuration (GH-466), also localises decisions';
    }

    public function up(Schema $schema): void
    {
        // phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
        $this->addSql('ALTER TABLE decision ADD contentEN TEXT NOT NULL');
        $this->addSql('ALTER TABLE decision RENAME COLUMN content TO contentNL');
        $this->addSql('ALTER TABLE subdecision ADD contentEN TEXT NOT NULL');
        $this->addSql('ALTER TABLE subdecision RENAME COLUMN content TO contentNL');
        // phpcs:enable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
    }

    public function down(Schema $schema): void
    {
        // phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE Decision ADD content TEXT NOT NULL');
        $this->addSql('ALTER TABLE Decision DROP contentNL');
        $this->addSql('ALTER TABLE Decision DROP contentEN');
        $this->addSql('ALTER TABLE SubDecision ADD content TEXT NOT NULL');
        $this->addSql('ALTER TABLE SubDecision DROP contentNL');
        $this->addSql('ALTER TABLE SubDecision DROP contentEN');
        // phpcs:enable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
    }
}
