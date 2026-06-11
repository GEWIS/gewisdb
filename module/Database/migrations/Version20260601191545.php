<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260601191545 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add fields to AuditEntry for mailing list membership audits';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auditentry ADD action VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE auditentry ADD mailing_list VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE auditentry ADD CONSTRAINT FK_DE382FBB15C473AF FOREIGN KEY (mailing_list) REFERENCES MailingList (name) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_DE382FBB15C473AF ON auditentry (mailing_list)');
        $this->addSql('ALTER TABLE auditentry ADD email VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE auditentry ADD origin VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE AuditEntry DROP action');
        $this->addSql('ALTER TABLE AuditEntry DROP CONSTRAINT FK_DE382FBB15C473AF');
        $this->addSql('DROP INDEX IDX_DE382FBB15C473AF');
        $this->addSql('ALTER TABLE AuditEntry DROP mailing_list');
        $this->addSql('ALTER TABLE AuditEntry DROP email');
        $this->addSql('ALTER TABLE AuditEntry DROP origin');
    }
}
