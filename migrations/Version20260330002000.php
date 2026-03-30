<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260330002000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create news_list_view read model table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE news_list_view (
                id UUID PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                created_at TIMESTAMP NOT NULL,
                published BOOLEAN NOT NULL DEFAULT false,
                published_at TIMESTAMP NULL
            )
        ');

        $this->addSql('CREATE INDEX idx_news_list_published ON news_list_view (published, published_at DESC)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS news_list_view');
    }
}
