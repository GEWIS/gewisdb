<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260331181824 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ListmonkMailingList (id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, lastSeen TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, lastCheck TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE mailinglist ADD listmonkId VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE mailinglist ADD CONSTRAINT FK_FD864C3AB97ED0D8 FOREIGN KEY (listmonkId) REFERENCES ListmonkMailingList (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FD864C3AB97ED0D8 ON mailinglist (listmonkId)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE MailingList DROP CONSTRAINT FK_FD864C3AB97ED0D8');
        $this->addSql('DROP TABLE ListmonkMailingList');
        $this->addSql('DROP INDEX UNIQ_FD864C3AB97ED0D8');
        $this->addSql('ALTER TABLE MailingList DROP listmonkId');
    }
}
