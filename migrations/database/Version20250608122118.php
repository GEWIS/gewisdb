<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20250608122118 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix which field is used for the name of a BM/GMM body in their body regulation decision.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE subdecision SET abbr = name, name = NULL WHERE type = \'organ_regulation\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE subdecision SET name = abbr, abbr = NULL WHERE type = \'organ_regulation\'');
    }
}
