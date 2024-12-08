<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 */
final class Version20241124183711 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove organ function configuration (GH-466)';
    }

    public function up(Schema $schema): void
    {
        // phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
        $this->addSql('DROP SEQUENCE installationfunction_id_seq CASCADE');
        $this->addSql('DROP TABLE installationfunction');
        // phpcs:enable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
    }

    public function down(Schema $schema): void
    {
        // phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SEQUENCE installationfunction_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE installationfunction (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        // phpcs:enable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
    }
}
