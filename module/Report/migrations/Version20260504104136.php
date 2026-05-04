<?php

declare(strict_types=1);

namespace Report\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260504104136 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow bodies to have a purpose, initially limited to voting committees';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE subdecision ADD purpose VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE SubDecision DROP purpose');
    }
}
