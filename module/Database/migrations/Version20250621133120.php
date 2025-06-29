<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20250621133120 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Stores mailing lists for prospective members in the prospective member table';
    }

    public function up(Schema $schema): void
    {
        // Set up the new way of storing lists for prospective members
        $this->addSql('ALTER TABLE prospectivemember ADD lists TEXT DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN prospectivemember.lists IS \'(DC2Type:simple_array)\'');

        // Data prospective_members_mailinglists -> simple_array in prospectivemember
        if ($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            $this->addSql('UPDATE prospectivemember as pm SET lists = (SELECT STRING_AGG(DISTINCT name, \',\') as lists FROM prospective_members_mailinglists as pl WHERE pl.lidnr = pm.lidnr GROUP BY pl.lidnr)');
        } else {
            $this->addSql('UPDATE prospectivemember as pm SET lists = (SELECT GROUP_CONCAT(DISTINCT name SEPARATOR \',\') as lists FROM prospective_members_mailinglists as pl WHERE pl.lidnr = pm.lidnr GROUP BY pl.lidnr)');
        }

        // Remove old prospective_members_mailinglists
        $this->addSql('ALTER TABLE prospective_members_mailinglists DROP CONSTRAINT fk_c86f04985e237e06');
        $this->addSql('ALTER TABLE prospective_members_mailinglists DROP CONSTRAINT fk_c86f0498d665e01d');
        $this->addSql('DROP TABLE prospective_members_mailinglists');
    }

    public function down(Schema $schema): void
    {
        // Recreate the old table
        $this->addSql('CREATE TABLE prospective_members_mailinglists (lidnr INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(lidnr, name))');
        $this->addSql('CREATE INDEX idx_c86f04985e237e06 ON prospective_members_mailinglists (name)');
        $this->addSql('CREATE INDEX idx_c86f0498d665e01d ON prospective_members_mailinglists (lidnr)');
        $this->addSql('ALTER TABLE prospective_members_mailinglists ADD CONSTRAINT fk_c86f04985e237e06 FOREIGN KEY (name) REFERENCES mailinglist (name) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE prospective_members_mailinglists ADD CONSTRAINT fk_c86f0498d665e01d FOREIGN KEY (lidnr) REFERENCES prospectivemember (lidnr) NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Move data back MailingListMember -> members_mailinglists
        if ($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            $this->addSql('INSERT INTO prospective_members_mailinglists (lidnr, name) (SELECT lidnr, UNNEST(STRING_TO_ARRAY(lists, \',\')) as name FROM prospectivemember)');
        } else {
            $this->abortIf(true, 'Explode-like functionality is only supported on PostgreSQL; continuing will drop all mailing list subscriptions for prospective members');
        }

        // Undo creation of the new table
        $this->addSql('ALTER TABLE ProspectiveMember DROP lists');
    }
}
