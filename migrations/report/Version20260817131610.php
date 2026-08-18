<?php

declare(strict_types=1);

namespace Report\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260817131610 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Stops organs and subdecisions from being deleted through the table relating them';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organs_subdecisions DROP CONSTRAINT FK_6177E308602FAFFB96F82E1690E0342DEF6BE237DD50EB88');
        $this->addSql('ALTER TABLE organs_subdecisions DROP CONSTRAINT FK_6177E308E4445171');
        $this->addSql('ALTER TABLE organs_subdecisions ADD CONSTRAINT FK_6177E308602FAFFB96F82E1690E0342DEF6BE237DD50EB88 FOREIGN KEY (meeting_type, meeting_number, decision_point, decision_number, subdecision_sequence) REFERENCES SubDecision (meeting_type, meeting_number, decision_point, decision_number, sequence) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organs_subdecisions ADD CONSTRAINT FK_6177E308E4445171 FOREIGN KEY (organ_id) REFERENCES Organ (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organs_subdecisions DROP CONSTRAINT fk_6177e308e4445171');
        $this->addSql('ALTER TABLE organs_subdecisions DROP CONSTRAINT fk_6177e308602faffb96f82e1690e0342def6be237dd50eb88');
        $this->addSql('ALTER TABLE organs_subdecisions ADD CONSTRAINT fk_6177e308e4445171 FOREIGN KEY (organ_id) REFERENCES organ (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organs_subdecisions ADD CONSTRAINT fk_6177e308602faffb96f82e1690e0342def6be237dd50eb88 FOREIGN KEY (meeting_type, meeting_number, decision_point, decision_number, subdecision_sequence) REFERENCES subdecision (meeting_type, meeting_number, decision_point, decision_number, sequence) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
