<?php

declare(strict_types=1);

namespace Report\Migrations;

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
        $this->addSql('CREATE TABLE MailingListMember (email VARCHAR(255) NOT NULL, member INT NOT NULL, mailingList VARCHAR(255) NOT NULL, PRIMARY KEY(mailingList, member, email))');
        $this->addSql('CREATE INDEX IDX_3A8467A97B1AC3ED ON MailingListMember (mailingList)');
        $this->addSql('CREATE INDEX IDX_3A8467A970E4FA78 ON MailingListMember (member)');
        $this->addSql('CREATE UNIQUE INDEX mailinglistmember_unique_idx ON MailingListMember (mailingList, member)');
        $this->addSql('ALTER TABLE MailingListMember ADD CONSTRAINT FK_3A8467A97B1AC3ED FOREIGN KEY (mailingList) REFERENCES MailingList (name) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE MailingListMember ADD CONSTRAINT FK_3A8467A970E4FA78 FOREIGN KEY (member) REFERENCES Member (lidnr) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE members_mailinglists DROP CONSTRAINT fk_5ad357d95e237e06');
        $this->addSql('ALTER TABLE members_mailinglists DROP CONSTRAINT fk_5ad357d9d665e01d');
        $this->addSql('DROP TABLE members_mailinglists');
        $this->addSql('ALTER TABLE mailinglist DROP onform');
        $this->addSql('ALTER TABLE mailinglist DROP defaultsub');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE members_mailinglists (lidnr INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(lidnr, name))');
        $this->addSql('CREATE INDEX idx_5ad357d95e237e06 ON members_mailinglists (name)');
        $this->addSql('CREATE INDEX idx_5ad357d9d665e01d ON members_mailinglists (lidnr)');
        $this->addSql('ALTER TABLE members_mailinglists ADD CONSTRAINT fk_5ad357d95e237e06 FOREIGN KEY (name) REFERENCES mailinglist (name) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE members_mailinglists ADD CONSTRAINT fk_5ad357d9d665e01d FOREIGN KEY (lidnr) REFERENCES member (lidnr) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE MailingListMember DROP CONSTRAINT FK_3A8467A97B1AC3ED');
        $this->addSql('ALTER TABLE MailingListMember DROP CONSTRAINT FK_3A8467A970E4FA78');
        $this->addSql('DROP TABLE MailingListMember');
        $this->addSql('ALTER TABLE MailingList ADD onform BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE MailingList ADD defaultsub BOOLEAN NOT NULL');
    }
}
