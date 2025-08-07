<?php

declare(strict_types=1);

namespace Report\Migrations;

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
        return 'Remove MailingList fields not used in report';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE MailingList DROP onform');
        $this->addSql('ALTER TABLE MailingList DROP defaultsub');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE MailingList ADD onform BOOLEAN NOT NULL DEFAULT false');
        $this->addSql('ALTER TABLE MailingList ADD defaultsub BOOLEAN NOT NULL DEFAULT false');
        $this->addSql('ALTER TABLE MailingList ALTER COLUMN onform DROP DEFAULT');
        $this->addSql('ALTER TABLE MailingList ALTER COLUMN defaultsub DROP DEFAULT');
    }
}
