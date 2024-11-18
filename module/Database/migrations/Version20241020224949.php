<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 */
final class Version20241020224949 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial migration after adding support for Doctrine migrations.';
    }

    public function up(Schema $schema): void
    {
        // phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
        $this->addSql('CREATE SEQUENCE ActionLink_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE ApiPrincipal_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE AuditEntry_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE CheckoutSession_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE ConfigItem_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE InstallationFunction_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE Member_lidnr_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE ProspectiveMember_lidnr_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE SavedQuery_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE users_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE ActionLink (id INT NOT NULL, prospective_member INT DEFAULT NULL, member INT DEFAULT NULL, used BOOLEAN NOT NULL, token VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, currentExpiration DATE DEFAULT NULL, newExpiration DATE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_A952B2A5740EE3E7 ON ActionLink (prospective_member)');
        $this->addSql('CREATE INDEX IDX_A952B2A570E4FA78 ON ActionLink (member)');
        $this->addSql('CREATE TABLE Address (type VARCHAR(255) NOT NULL, lidnr INT NOT NULL, country VARCHAR(255) NOT NULL, street VARCHAR(255) NOT NULL, number VARCHAR(255) NOT NULL, postalCode VARCHAR(255) NOT NULL, city VARCHAR(255) NOT NULL, phone VARCHAR(255) NOT NULL, PRIMARY KEY(lidnr, type))');
        $this->addSql('CREATE INDEX IDX_C2F3561DD665E01D ON Address (lidnr)');
        $this->addSql('CREATE TABLE ApiPrincipal (id INT NOT NULL, token VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, permissions TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN ApiPrincipal.permissions IS \'(DC2Type:simple_array)\'');
        $this->addSql('CREATE TABLE AuditEntry (id INT NOT NULL, user_id INT DEFAULT NULL, member INT DEFAULT NULL, createdAt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updatedAt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, type VARCHAR(255) NOT NULL, note VARCHAR(255) DEFAULT NULL, oldExpiration TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, newExpiration TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_DE382FBBA76ED395 ON AuditEntry (user_id)');
        $this->addSql('CREATE INDEX IDX_DE382FBB70E4FA78 ON AuditEntry (member)');
        $this->addSql('CREATE TABLE CheckoutSession (id INT NOT NULL, prospective_member INT DEFAULT NULL, recovered_from_id INT DEFAULT NULL, checkoutId VARCHAR(255) NOT NULL, created TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expiration TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, paymentIntentId VARCHAR(255) DEFAULT NULL, recoveryUrl VARCHAR(255) DEFAULT NULL, state INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BC63300E198D234 ON CheckoutSession (checkoutId)');
        $this->addSql('CREATE INDEX IDX_BC63300E740EE3E7 ON CheckoutSession (prospective_member)');
        $this->addSql('CREATE INDEX IDX_BC63300EE03E402D ON CheckoutSession (recovered_from_id)');
        $this->addSql('CREATE TABLE ConfigItem (id INT NOT NULL, namespace VARCHAR(255) NOT NULL, key VARCHAR(255) NOT NULL, valueString VARCHAR(255) DEFAULT NULL, valueDate TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, createdAt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updatedAt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX configitem_unique_idx ON ConfigItem (namespace, key)');
        $this->addSql('CREATE TABLE Decision (meeting_type VARCHAR(255) NOT NULL, meeting_number INT NOT NULL, point INT NOT NULL, number INT NOT NULL, PRIMARY KEY(meeting_type, meeting_number, point, number))');
        $this->addSql('CREATE INDEX IDX_7DDADC1E602FAFFB96F82E16 ON Decision (meeting_type, meeting_number)');
        $this->addSql('CREATE TABLE InstallationFunction (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE MailingList (name VARCHAR(255) NOT NULL, nl_description TEXT NOT NULL, en_description TEXT NOT NULL, onForm BOOLEAN NOT NULL, defaultSub BOOLEAN NOT NULL, PRIMARY KEY(name))');
        $this->addSql('CREATE TABLE Meeting (type VARCHAR(255) NOT NULL, number INT NOT NULL, date DATE NOT NULL, PRIMARY KEY(type, number))');
        $this->addSql('CREATE TABLE Member (lidnr INT NOT NULL, email VARCHAR(255) DEFAULT NULL, lastName VARCHAR(255) NOT NULL, middleName VARCHAR(255) NOT NULL, initials VARCHAR(255) NOT NULL, firstName VARCHAR(255) NOT NULL, generation INT NOT NULL, tueUsername VARCHAR(255) DEFAULT NULL, study VARCHAR(255) DEFAULT NULL, type VARCHAR(255) NOT NULL, changedOn DATE NOT NULL, isStudying BOOLEAN NOT NULL, membershipEndsOn DATE DEFAULT NULL, expiration DATE NOT NULL, lastCheckedOn DATE DEFAULT NULL, birth DATE NOT NULL, paid INT NOT NULL, supremum VARCHAR(255) DEFAULT NULL, hidden BOOLEAN DEFAULT false NOT NULL, authenticationKey VARCHAR(255) DEFAULT NULL, deleted BOOLEAN DEFAULT false NOT NULL, PRIMARY KEY(lidnr))');
        $this->addSql('CREATE TABLE members_mailinglists (lidnr INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(lidnr, name))');
        $this->addSql('CREATE INDEX IDX_5AD357D9D665E01D ON members_mailinglists (lidnr)');
        $this->addSql('CREATE INDEX IDX_5AD357D95E237E06 ON members_mailinglists (name)');
        $this->addSql('CREATE TABLE MemberUpdate (lidnr INT NOT NULL, requestedDate DATE NOT NULL, email VARCHAR(255) NOT NULL, lastName VARCHAR(255) NOT NULL, middleName VARCHAR(255) NOT NULL, initials VARCHAR(255) NOT NULL, firstName VARCHAR(255) NOT NULL, PRIMARY KEY(lidnr))');
        $this->addSql('CREATE TABLE ProspectiveMember (lidnr INT NOT NULL, email VARCHAR(255) NOT NULL, lastName VARCHAR(255) NOT NULL, middleName VARCHAR(255) NOT NULL, initials VARCHAR(255) NOT NULL, firstName VARCHAR(255) NOT NULL, tueUsername VARCHAR(255) DEFAULT NULL, study VARCHAR(255) DEFAULT NULL, changedOn DATE NOT NULL, birth DATE NOT NULL, paid INT NOT NULL, country VARCHAR(255) NOT NULL, street VARCHAR(255) NOT NULL, number VARCHAR(255) NOT NULL, postalCode VARCHAR(255) NOT NULL, city VARCHAR(255) NOT NULL, phone VARCHAR(255) NOT NULL, PRIMARY KEY(lidnr))');
        $this->addSql('CREATE TABLE prospective_members_mailinglists (lidnr INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(lidnr, name))');
        $this->addSql('CREATE INDEX IDX_C86F0498D665E01D ON prospective_members_mailinglists (lidnr)');
        $this->addSql('CREATE INDEX IDX_C86F04985E237E06 ON prospective_members_mailinglists (name)');
        $this->addSql('CREATE TABLE SavedQuery (id INT NOT NULL, name VARCHAR(255) NOT NULL, query TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE SubDecision (meeting_type VARCHAR(255) NOT NULL, meeting_number INT NOT NULL, decision_point INT NOT NULL, decision_number INT NOT NULL, sequence INT NOT NULL, lidnr INT DEFAULT NULL, r_meeting_type VARCHAR(255) DEFAULT NULL, r_meeting_number INT DEFAULT NULL, r_decision_point INT DEFAULT NULL, r_decision_number INT DEFAULT NULL, r_sequence INT DEFAULT NULL, type VARCHAR(255) NOT NULL, name VARCHAR(255) DEFAULT NULL, organType VARCHAR(255) DEFAULT NULL, version VARCHAR(32) DEFAULT NULL, date DATE DEFAULT NULL, approval BOOLEAN DEFAULT NULL, changes BOOLEAN DEFAULT NULL, abbr VARCHAR(255) DEFAULT NULL, function VARCHAR(255) DEFAULT NULL, content TEXT DEFAULT NULL, until DATE DEFAULT NULL, withdrawnOn DATE DEFAULT NULL, PRIMARY KEY(meeting_type, meeting_number, decision_point, decision_number, sequence))');
        $this->addSql('CREATE INDEX IDX_F0D6EE40602FAFFB96F82E1690E0342DEF6BE237 ON SubDecision (meeting_type, meeting_number, decision_point, decision_number)');
        $this->addSql('CREATE INDEX IDX_F0D6EE40D665E01D ON SubDecision (lidnr)');
        $this->addSql('CREATE INDEX IDX_F0D6EE40EFBA85FF292FAD512F37B76A76CE1878B79BB36 ON SubDecision (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence)');
        $this->addSql('CREATE INDEX IDX_F0D6EE40EFBA85FF292FAD512F37B76A76CE187 ON SubDecision (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number)');
        $this->addSql('CREATE INDEX IDX_F0D6EE40EFBA85FF292FAD51 ON SubDecision (r_meeting_type, r_meeting_number)');
        $this->addSql('CREATE TABLE users (id INT NOT NULL, login VARCHAR(255) NOT NULL, password VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE ActionLink ADD CONSTRAINT FK_A952B2A5740EE3E7 FOREIGN KEY (prospective_member) REFERENCES ProspectiveMember (lidnr) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ActionLink ADD CONSTRAINT FK_A952B2A570E4FA78 FOREIGN KEY (member) REFERENCES Member (lidnr) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE Address ADD CONSTRAINT FK_C2F3561DD665E01D FOREIGN KEY (lidnr) REFERENCES Member (lidnr) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE AuditEntry ADD CONSTRAINT FK_DE382FBBA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE AuditEntry ADD CONSTRAINT FK_DE382FBB70E4FA78 FOREIGN KEY (member) REFERENCES Member (lidnr) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE CheckoutSession ADD CONSTRAINT FK_BC63300E740EE3E7 FOREIGN KEY (prospective_member) REFERENCES ProspectiveMember (lidnr) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE CheckoutSession ADD CONSTRAINT FK_BC63300EE03E402D FOREIGN KEY (recovered_from_id) REFERENCES CheckoutSession (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE Decision ADD CONSTRAINT FK_7DDADC1E602FAFFB96F82E16 FOREIGN KEY (meeting_type, meeting_number) REFERENCES Meeting (type, number) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE members_mailinglists ADD CONSTRAINT FK_5AD357D9D665E01D FOREIGN KEY (lidnr) REFERENCES Member (lidnr) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE members_mailinglists ADD CONSTRAINT FK_5AD357D95E237E06 FOREIGN KEY (name) REFERENCES MailingList (name) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE MemberUpdate ADD CONSTRAINT FK_6FA192D9D665E01D FOREIGN KEY (lidnr) REFERENCES Member (lidnr) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE prospective_members_mailinglists ADD CONSTRAINT FK_C86F0498D665E01D FOREIGN KEY (lidnr) REFERENCES ProspectiveMember (lidnr) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE prospective_members_mailinglists ADD CONSTRAINT FK_C86F04985E237E06 FOREIGN KEY (name) REFERENCES MailingList (name) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE SubDecision ADD CONSTRAINT FK_F0D6EE40602FAFFB96F82E1690E0342DEF6BE237 FOREIGN KEY (meeting_type, meeting_number, decision_point, decision_number) REFERENCES Decision (meeting_type, meeting_number, point, number) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE SubDecision ADD CONSTRAINT FK_F0D6EE40D665E01D FOREIGN KEY (lidnr) REFERENCES Member (lidnr) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE SubDecision ADD CONSTRAINT FK_F0D6EE40EFBA85FF292FAD512F37B76A76CE1878B79BB36 FOREIGN KEY (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence) REFERENCES SubDecision (meeting_type, meeting_number, decision_point, decision_number, sequence) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE SubDecision ADD CONSTRAINT FK_F0D6EE40EFBA85FF292FAD512F37B76A76CE187 FOREIGN KEY (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number) REFERENCES Decision (meeting_type, meeting_number, point, number) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE SubDecision ADD CONSTRAINT FK_F0D6EE40EFBA85FF292FAD51 FOREIGN KEY (r_meeting_type, r_meeting_number) REFERENCES Meeting (type, number) NOT DEFERRABLE INITIALLY IMMEDIATE');
        // phpcs:enable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
    }

    public function down(Schema $schema): void
    {
        // phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
        $this->throwIrreversibleMigrationException();
        // phpcs:enable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
    }
}
