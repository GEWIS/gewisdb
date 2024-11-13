<?php

declare(strict_types=1);

namespace Report\Migrations;

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
        $this->addSql('CREATE SEQUENCE BoardMember_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE Keyholder_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE Organ_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE OrganMember_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE Address (type VARCHAR(255) NOT NULL, lidnr INT NOT NULL, country VARCHAR(255) NOT NULL, street VARCHAR(255) NOT NULL, number VARCHAR(255) NOT NULL, postalCode VARCHAR(255) NOT NULL, city VARCHAR(255) NOT NULL, phone VARCHAR(255) NOT NULL, PRIMARY KEY(lidnr, type))');
        $this->addSql('CREATE INDEX IDX_C2F3561DD665E01D ON Address (lidnr)');
        $this->addSql('CREATE TABLE BoardMember (id INT NOT NULL, lidnr INT NOT NULL, r_meeting_type VARCHAR(255) DEFAULT NULL, r_meeting_number INT DEFAULT NULL, r_decision_point INT DEFAULT NULL, r_decision_number INT DEFAULT NULL, r_sequence INT DEFAULT NULL, function VARCHAR(255) NOT NULL, installDate DATE NOT NULL, releaseDate DATE DEFAULT NULL, dischargeDate DATE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D9517B2ED665E01D ON BoardMember (lidnr)');
        $this->addSql('CREATE UNIQUE INDEX installationDec_uniq ON BoardMember (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence)');
        $this->addSql('CREATE TABLE Decision (meeting_type VARCHAR(255) NOT NULL, meeting_number INT NOT NULL, point INT NOT NULL, number INT NOT NULL, content TEXT NOT NULL, PRIMARY KEY(meeting_type, meeting_number, point, number))');
        $this->addSql('CREATE INDEX IDX_7DDADC1E602FAFFB96F82E16 ON Decision (meeting_type, meeting_number)');
        $this->addSql('CREATE TABLE Keyholder (id INT NOT NULL, lidnr INT NOT NULL, r_meeting_type VARCHAR(255) DEFAULT NULL, r_meeting_number INT DEFAULT NULL, r_decision_point INT DEFAULT NULL, r_decision_number INT DEFAULT NULL, r_sequence INT DEFAULT NULL, expirationDate DATE NOT NULL, withdrawnDate DATE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3C5F7B4DD665E01D ON Keyholder (lidnr)');
        $this->addSql('CREATE UNIQUE INDEX grantingDec_uniq ON Keyholder (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence)');
        $this->addSql('CREATE TABLE MailingList (name VARCHAR(255) NOT NULL, nl_description TEXT NOT NULL, en_description TEXT NOT NULL, onForm BOOLEAN NOT NULL, defaultSub BOOLEAN NOT NULL, PRIMARY KEY(name))');
        $this->addSql('CREATE TABLE Meeting (type VARCHAR(255) NOT NULL, number INT NOT NULL, date DATE NOT NULL, PRIMARY KEY(type, number))');
        $this->addSql('CREATE TABLE Member (lidnr INT NOT NULL, email VARCHAR(255) DEFAULT NULL, lastName VARCHAR(255) NOT NULL, middleName VARCHAR(255) NOT NULL, initials VARCHAR(255) NOT NULL, firstName VARCHAR(255) NOT NULL, generation INT NOT NULL, type VARCHAR(255) NOT NULL, changedOn DATE NOT NULL, membershipEndsOn DATE DEFAULT NULL, birth DATE NOT NULL, expiration DATE NOT NULL, paid INT NOT NULL, supremum VARCHAR(255) DEFAULT NULL, hidden BOOLEAN DEFAULT false NOT NULL, authenticationKey VARCHAR(255) DEFAULT NULL, deleted BOOLEAN DEFAULT false NOT NULL, PRIMARY KEY(lidnr))');
        $this->addSql('CREATE TABLE members_mailinglists (lidnr INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(lidnr, name))');
        $this->addSql('CREATE INDEX IDX_5AD357D9D665E01D ON members_mailinglists (lidnr)');
        $this->addSql('CREATE INDEX IDX_5AD357D95E237E06 ON members_mailinglists (name)');
        $this->addSql('CREATE TABLE Organ (id INT NOT NULL, r_meeting_type VARCHAR(255) DEFAULT NULL, r_meeting_number INT DEFAULT NULL, r_decision_point INT DEFAULT NULL, r_decision_number INT DEFAULT NULL, r_sequence INT DEFAULT NULL, abbr VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, foundationDate DATE NOT NULL, abrogationDate DATE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX foundation_uniq ON Organ (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence)');
        $this->addSql('CREATE TABLE organs_subdecisions (organ_id INT NOT NULL, meeting_type VARCHAR(255) NOT NULL, meeting_number INT NOT NULL, decision_point INT NOT NULL, decision_number INT NOT NULL, subdecision_sequence INT NOT NULL, PRIMARY KEY(organ_id, meeting_type, meeting_number, decision_point, decision_number, subdecision_sequence))');
        $this->addSql('CREATE INDEX IDX_6177E308E4445171 ON organs_subdecisions (organ_id)');
        $this->addSql('CREATE INDEX IDX_6177E308602FAFFB96F82E1690E0342DEF6BE237DD50EB88 ON organs_subdecisions (meeting_type, meeting_number, decision_point, decision_number, subdecision_sequence)');
        $this->addSql('CREATE TABLE OrganMember (id INT NOT NULL, organ_id INT DEFAULT NULL, lidnr INT DEFAULT NULL, r_meeting_type VARCHAR(255) DEFAULT NULL, r_meeting_number INT DEFAULT NULL, r_decision_point INT DEFAULT NULL, r_decision_number INT DEFAULT NULL, r_sequence INT DEFAULT NULL, function VARCHAR(255) NOT NULL, installDate DATE NOT NULL, dischargeDate DATE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E5CB2C7DE4445171 ON OrganMember (organ_id)');
        $this->addSql('CREATE INDEX IDX_E5CB2C7DD665E01D ON OrganMember (lidnr)');
        $this->addSql('CREATE UNIQUE INDEX installation_uniq ON OrganMember (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence)');
        $this->addSql('CREATE TABLE SubDecision (meeting_type VARCHAR(255) NOT NULL, meeting_number INT NOT NULL, decision_point INT NOT NULL, decision_number INT NOT NULL, sequence INT NOT NULL, lidnr INT DEFAULT NULL, r_meeting_type VARCHAR(255) DEFAULT NULL, r_meeting_number INT DEFAULT NULL, r_decision_point INT DEFAULT NULL, r_decision_number INT DEFAULT NULL, r_sequence INT DEFAULT NULL, content TEXT NOT NULL, type VARCHAR(255) NOT NULL, name VARCHAR(255) DEFAULT NULL, organType VARCHAR(255) DEFAULT NULL, version VARCHAR(32) DEFAULT NULL, date DATE DEFAULT NULL, approval BOOLEAN DEFAULT NULL, changes BOOLEAN DEFAULT NULL, abbr VARCHAR(255) DEFAULT NULL, function VARCHAR(255) DEFAULT NULL, until DATE DEFAULT NULL, withdrawnOn DATE DEFAULT NULL, PRIMARY KEY(meeting_type, meeting_number, decision_point, decision_number, sequence))');
        $this->addSql('CREATE INDEX IDX_F0D6EE40602FAFFB96F82E1690E0342DEF6BE237 ON SubDecision (meeting_type, meeting_number, decision_point, decision_number)');
        $this->addSql('CREATE INDEX IDX_F0D6EE40D665E01D ON SubDecision (lidnr)');
        $this->addSql('CREATE INDEX IDX_F0D6EE40EFBA85FF292FAD512F37B76A76CE1878B79BB36 ON SubDecision (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence)');
        $this->addSql('CREATE INDEX IDX_F0D6EE40EFBA85FF292FAD512F37B76A76CE187 ON SubDecision (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number)');
        $this->addSql('CREATE INDEX IDX_F0D6EE40EFBA85FF292FAD51 ON SubDecision (r_meeting_type, r_meeting_number)');
        $this->addSql('ALTER TABLE Address ADD CONSTRAINT FK_C2F3561DD665E01D FOREIGN KEY (lidnr) REFERENCES Member (lidnr) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE BoardMember ADD CONSTRAINT FK_D9517B2ED665E01D FOREIGN KEY (lidnr) REFERENCES Member (lidnr) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE BoardMember ADD CONSTRAINT FK_D9517B2EEFBA85FF292FAD512F37B76A76CE1878B79BB36 FOREIGN KEY (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence) REFERENCES SubDecision (meeting_type, meeting_number, decision_point, decision_number, sequence) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE Decision ADD CONSTRAINT FK_7DDADC1E602FAFFB96F82E16 FOREIGN KEY (meeting_type, meeting_number) REFERENCES Meeting (type, number) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE Keyholder ADD CONSTRAINT FK_3C5F7B4DD665E01D FOREIGN KEY (lidnr) REFERENCES Member (lidnr) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE Keyholder ADD CONSTRAINT FK_3C5F7B4DEFBA85FF292FAD512F37B76A76CE1878B79BB36 FOREIGN KEY (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence) REFERENCES SubDecision (meeting_type, meeting_number, decision_point, decision_number, sequence) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE members_mailinglists ADD CONSTRAINT FK_5AD357D9D665E01D FOREIGN KEY (lidnr) REFERENCES Member (lidnr) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE members_mailinglists ADD CONSTRAINT FK_5AD357D95E237E06 FOREIGN KEY (name) REFERENCES MailingList (name) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE Organ ADD CONSTRAINT FK_46C39B8EEFBA85FF292FAD512F37B76A76CE1878B79BB36 FOREIGN KEY (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence) REFERENCES SubDecision (meeting_type, meeting_number, decision_point, decision_number, sequence) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organs_subdecisions ADD CONSTRAINT FK_6177E308E4445171 FOREIGN KEY (organ_id) REFERENCES Organ (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organs_subdecisions ADD CONSTRAINT FK_6177E308602FAFFB96F82E1690E0342DEF6BE237DD50EB88 FOREIGN KEY (meeting_type, meeting_number, decision_point, decision_number, subdecision_sequence) REFERENCES SubDecision (meeting_type, meeting_number, decision_point, decision_number, sequence) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE OrganMember ADD CONSTRAINT FK_E5CB2C7DE4445171 FOREIGN KEY (organ_id) REFERENCES Organ (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE OrganMember ADD CONSTRAINT FK_E5CB2C7DD665E01D FOREIGN KEY (lidnr) REFERENCES Member (lidnr) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE OrganMember ADD CONSTRAINT FK_E5CB2C7DEFBA85FF292FAD512F37B76A76CE1878B79BB36 FOREIGN KEY (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence) REFERENCES SubDecision (meeting_type, meeting_number, decision_point, decision_number, sequence) NOT DEFERRABLE INITIALLY IMMEDIATE');
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
