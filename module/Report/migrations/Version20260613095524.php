<?php

declare(strict_types=1);

namespace Report\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260613095524 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove paid attribute from member.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE member DROP paid');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Member ADD paid INT DEFAULT NULL');
        $this->addSql('UPDATE Member SET paid = 0 WHERE paid IS NULL');
        $this->addSql('ALTER TABLE Member ALTER paid SET NOT NULL');
    }
}
