<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20250821111612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Keep track of creation and modification dates of users and api principals';
    }

    public function up(Schema $schema): void
    {
        $now = new DateTime()->format('Y-m-d');
        $this->addSql('ALTER TABLE apiprincipal ADD createdAt TIMESTAMP(0) WITHOUT TIME ZONE, ADD updatedAt TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('UPDATE apiprincipal set createdAt = \'' . $now . '\', updatedAt = \'' . $now . '\'');
        $this->addSql('ALTER TABLE apiprincipal ALTER COLUMN createdAt SET NOT NULL, ALTER COLUMN updatedAt SET NOT NULL;');

        $this->addSql('ALTER TABLE users ADD createdAt TIMESTAMP(0) WITHOUT TIME ZONE, ADD updatedAt TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('UPDATE users set createdAt = \'' . $now . '\', updatedAt = \'' . $now . '\'');
        $this->addSql('ALTER TABLE users ALTER COLUMN createdAt SET NOT NULL, ALTER COLUMN updatedAt SET NOT NULL;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP createdAt, DROP updatedAt');
        $this->addSql('ALTER TABLE ApiPrincipal DROP createdAt, DROP updatedAt');
    }
}
