<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260331001000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add private column to news_list_view read model';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE news_list_view ADD COLUMN private BOOLEAN NOT NULL DEFAULT true');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE news_list_view DROP COLUMN private');
    }
}
