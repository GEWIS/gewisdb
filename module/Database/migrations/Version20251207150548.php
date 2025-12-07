<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251207150548 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Implement versioning of config items for optimistic locking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE configitem ADD version INT DEFAULT 1000 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ConfigItem DROP version');
    }
}
