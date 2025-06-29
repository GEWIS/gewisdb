<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20250621133118 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Introduces linking mailman lists to mailing lists';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE MailmanMailingList (id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, lastSeen TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, lastCheck TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE mailinglist ADD mailmanId VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE mailinglist ADD CONSTRAINT FK_FD864C3AFD6980D2 FOREIGN KEY (mailmanId) REFERENCES MailmanMailingList (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FD864C3AFD6980D2 ON mailinglist (mailmanId)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE MailingList DROP CONSTRAINT FK_FD864C3AFD6980D2');
        $this->addSql('DROP TABLE MailmanMailingList');
        $this->addSql('DROP INDEX UNIQ_FD864C3AFD6980D2');
        $this->addSql('ALTER TABLE MailingList DROP mailmanId');
    }
}
