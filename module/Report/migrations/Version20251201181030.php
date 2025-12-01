<?php

declare(strict_types=1);

namespace Report\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251201181030 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Removes member from the primary key of mailing list members';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mailinglistmember DROP CONSTRAINT mailinglistmember_pkey');
        $this->addSql('ALTER TABLE mailinglistmember ALTER member DROP NOT NULL');
        $this->addSql('ALTER TABLE mailinglistmember ADD PRIMARY KEY (mailingList, email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mailinglistmember DROP CONSTRAINT mailinglistmember_pkey');
        $this->addSql('ALTER TABLE mailinglistmember ALTER member SET NOT NULL');
        $this->addSql('ALTER TABLE mailinglistmember ADD PRIMARY KEY (mailinglist, member, email)');
    }
}
