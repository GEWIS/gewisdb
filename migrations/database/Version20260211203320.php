<?php

declare(strict_types=1);

namespace Database\Migrations;

use DateTime;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260211203320 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change config item for mailman sync to date value';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE configitem SET valuebool = null, valuedate = updatedat + interval \'23h\' * valuebool::int WHERE key = \'locked\' AND namespace = \'database_mailman\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE configitem SET valuebool = valuedate > \'' . (new DateTime())->format('Y-m-d H:i:s') . '\', valuedate = null WHERE key = \'locked\' AND namespace = \'database_mailman\'');
    }
}
