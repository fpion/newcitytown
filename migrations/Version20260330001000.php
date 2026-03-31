<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260330001000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create event_store table for Event Sourcing';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE event_store (
                id BIGSERIAL PRIMARY KEY,
                aggregate_id UUID NOT NULL,
                event_name VARCHAR(255) NOT NULL,
                payload JSONB NOT NULL DEFAULT \'{}\',
                occurred_at TIMESTAMP(6) NOT NULL,
                version INT NOT NULL,
                UNIQUE (aggregate_id, version)
            )
        ');

        $this->addSql('CREATE INDEX idx_event_store_aggregate ON event_store (aggregate_id, version)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS event_store');
    }
}
