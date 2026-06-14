<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260613120619 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Introduce membership as separate entity and remove membership-related fields from Member entity';
    }

    public function up(Schema $schema): void
    {
        // Add membership table
        $this->addSql('CREATE SEQUENCE Membership_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE Membership (id INT NOT NULL, member_lidnr INT NOT NULL, startDate DATE NOT NULL, endDate DATE NOT NULL, paid INT NOT NULL, type VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX membership_member_idx ON Membership (member_lidnr)');
        $this->addSql('CREATE UNIQUE INDEX membership_unique_idx ON Membership (member_lidnr, startDate)');
        $this->addSql('ALTER TABLE Membership ADD CONSTRAINT FK_C9A2D155B44475EE FOREIGN KEY (member_lidnr) REFERENCES Member (lidnr) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Assume all members were ordinary member between their generation and membership end date
        $this->addSql(<<<'SQL'
            INSERT INTO membership("id", "member_lidnr", "startdate", "enddate", "paid", "type")
            SELECT nextval('Membership_id_seq'), "lidnr", make_date("generation", 7, 1), "membershipendson", "paid", 'ordinary'
            FROM "member"
            WHERE "membershipendson" IS NOT NULL AND "deleted" = FALSE
        SQL);
        // Add graduate period as graduate membership if membership end date is set
        $this->addSql(<<<'SQL'
            INSERT INTO membership("id", "member_lidnr", "startdate", "enddate", "paid", "type")
            SELECT nextval('Membership_id_seq'), "lidnr", "membershipendson", "expiration", 0, 'graduate'
            FROM "member"
            WHERE "membershipendson" IS NOT NULL AND "membershipendson" < "expiration" AND "deleted" = FALSE
        SQL);

        // For members that do not have a membership end date, assume they were the same type from their generation until their expiration date.
        $this->addSql(<<<'SQL'
            INSERT INTO membership("id", "member_lidnr", "startdate", "enddate", "paid", "type")
            SELECT nextval('Membership_id_seq'), "lidnr", make_date("generation", 7, 1), "expiration", "paid", "type"
            FROM "member"
            WHERE "membershipendson" IS NULL AND "deleted" = FALSE
        SQL);

        // Remove membership-related fields from Member table
        $this->addSql('ALTER TABLE member DROP generation, DROP type, DROP isstudying, DROP membershipendson, DROP expiration, DROP paid');
    }

    public function down(Schema $schema): void
    {
        // Readd columns to Member table
        $this->addSql('ALTER TABLE Member ADD generation INT, ADD "type" VARCHAR(255), ADD isstudying BOOLEAN, ADD membershipendson DATE DEFAULT NULL, ADD expiration DATE, ADD paid INT');

        // Set values for the readded columns based on the Membership table
        $this->addSql(<<<'SQL'
            UPDATE member m
            SET generation = (SELECT EXTRACT(YEAR FROM ms.startDate) - (CASE WHEN EXTRACT(MONTH from ms.startDate) <= 6 THEN 1 ELSE 0 END) FROM membership ms WHERE m.lidnr = ms.member_lidnr ORDER BY startdate ASC LIMIT 1),
                "type" = (SELECT ms2.type FROM membership ms2 WHERE m.lidnr = ms2.member_lidnr ORDER BY enddate DESC LIMIT 1),
                isstudying = (SELECT (ms3.type = 'ordinary') FROM membership ms3 WHERE m.lidnr = ms3.member_lidnr ORDER BY enddate DESC LIMIT 1),
                membershipendson = (SELECT ms4.endDate FROM membership ms4 WHERE m.lidnr = ms4.member_lidnr AND ms4.type <> 'graduate' ORDER BY startdate DESC LIMIT 1),
                expiration = (SELECT ms5.endDate FROM membership ms5 WHERE m.lidnr = ms5.member_lidnr ORDER BY startdate DESC LIMIT 1),
                paid = (SELECT SUM(ms6.paid) FROM membership ms6 WHERE m.lidnr = ms6.member_lidnr GROUP BY ms6.member_lidnr)
        SQL);

        // Go back to previous convention of having membershipendson be null for members without a membership end date
        $this->addSql(<<<'SQL'
            UPDATE member m
            SET membershipendson = NULL
            WHERE membershipendson IS NOT NULL AND expiration IS NOT NULL AND membershipendson >= expiration
        SQL);

        // Set NOT NULL constraints on the readded columns
        $this->addSql('ALTER TABLE member ALTER COLUMN generation SET NOT NULL, ALTER COLUMN "type" SET NOT NULL, ALTER COLUMN isstudying SET NOT NULL, ALTER COLUMN expiration SET NOT NULL, ALTER COLUMN paid SET NOT NULL');

        // Drop membership table
        $this->addSql('DROP SEQUENCE Membership_id_seq CASCADE');
        $this->addSql('ALTER TABLE Membership DROP CONSTRAINT FK_C9A2D155B44475EE');
        $this->addSql('DROP TABLE Membership');
    }
}
