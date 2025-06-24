<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20250621133119 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Introduces the necessary tables for mailman mailing lists';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE MailingListMember (member INT NOT NULL, membershipId VARCHAR(255) DEFAULT NULL, lastSyncOn TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, lastSyncSuccess BOOLEAN NOT NULL, toBeDeleted BOOLEAN NOT NULL, mailingList VARCHAR(255) NOT NULL, PRIMARY KEY(mailingList, member))');
        $this->addSql('CREATE INDEX IDX_3A8467A97B1AC3ED ON MailingListMember (mailingList)');
        $this->addSql('CREATE INDEX IDX_3A8467A970E4FA78 ON MailingListMember (member)');
        $this->addSql('CREATE UNIQUE INDEX mailinglistmember_unique_idx ON MailingListMember (mailingList, member)');
        $this->addSql('CREATE TABLE MailmanMailingList (id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, lastSeen TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE MailingListMember ADD CONSTRAINT FK_3A8467A97B1AC3ED FOREIGN KEY (mailingList) REFERENCES MailingList (name) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE MailingListMember ADD CONSTRAINT FK_3A8467A970E4FA78 FOREIGN KEY (member) REFERENCES Member (lidnr) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE prospective_members_mailinglists DROP CONSTRAINT fk_c86f04985e237e06');
        $this->addSql('ALTER TABLE prospective_members_mailinglists DROP CONSTRAINT fk_c86f0498d665e01d');
        $this->addSql('ALTER TABLE members_mailinglists DROP CONSTRAINT fk_5ad357d95e237e06');
        $this->addSql('ALTER TABLE members_mailinglists DROP CONSTRAINT fk_5ad357d9d665e01d');
        $this->addSql('DROP TABLE prospective_members_mailinglists');
        $this->addSql('DROP TABLE members_mailinglists');
        $this->addSql('ALTER TABLE configitem ADD valueBool BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE mailinglist ADD mailmanId VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE mailinglist ADD CONSTRAINT FK_FD864C3AFD6980D2 FOREIGN KEY (mailmanId) REFERENCES MailmanMailingList (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FD864C3AFD6980D2 ON mailinglist (mailmanId)');
        $this->addSql('ALTER TABLE prospectivemember ADD lists TEXT DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN prospectivemember.lists IS \'(DC2Type:simple_array)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE MailingList DROP CONSTRAINT FK_FD864C3AFD6980D2');
        $this->addSql('CREATE TABLE prospective_members_mailinglists (lidnr INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(lidnr, name))');
        $this->addSql('CREATE INDEX idx_c86f04985e237e06 ON prospective_members_mailinglists (name)');
        $this->addSql('CREATE INDEX idx_c86f0498d665e01d ON prospective_members_mailinglists (lidnr)');
        $this->addSql('CREATE TABLE members_mailinglists (lidnr INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(lidnr, name))');
        $this->addSql('CREATE INDEX idx_5ad357d95e237e06 ON members_mailinglists (name)');
        $this->addSql('CREATE INDEX idx_5ad357d9d665e01d ON members_mailinglists (lidnr)');
        $this->addSql('ALTER TABLE prospective_members_mailinglists ADD CONSTRAINT fk_c86f04985e237e06 FOREIGN KEY (name) REFERENCES mailinglist (name) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE prospective_members_mailinglists ADD CONSTRAINT fk_c86f0498d665e01d FOREIGN KEY (lidnr) REFERENCES prospectivemember (lidnr) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE members_mailinglists ADD CONSTRAINT fk_5ad357d95e237e06 FOREIGN KEY (name) REFERENCES mailinglist (name) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE members_mailinglists ADD CONSTRAINT fk_5ad357d9d665e01d FOREIGN KEY (lidnr) REFERENCES member (lidnr) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE MailingListMember DROP CONSTRAINT FK_3A8467A97B1AC3ED');
        $this->addSql('ALTER TABLE MailingListMember DROP CONSTRAINT FK_3A8467A970E4FA78');
        $this->addSql('DROP TABLE MailingListMember');
        $this->addSql('DROP TABLE MailmanMailingList');
        $this->addSql('ALTER TABLE ConfigItem DROP valueBool');
        $this->addSql('DROP INDEX UNIQ_FD864C3AFD6980D2');
        $this->addSql('ALTER TABLE MailingList DROP mailmanId');
        $this->addSql('ALTER TABLE ProspectiveMember DROP lists');
    }
}
