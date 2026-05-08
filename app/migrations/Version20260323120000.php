<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260323120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add idempotency key to order for duplicate checkout protection';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` ADD idempotency_key VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ORDER_IDEMPOTENCY_KEY ON `order` (idempotency_key)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_ORDER_IDEMPOTENCY_KEY ON `order`');
        $this->addSql('ALTER TABLE `order` DROP idempotency_key');
    }
}
