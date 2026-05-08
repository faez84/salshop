<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260323124000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add provider order id field for external payment providers (PayPal)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` ADD provider_order_id VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ORDER_PROVIDER_ORDER_ID ON `order` (provider_order_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_ORDER_PROVIDER_ORDER_ID ON `order`');
        $this->addSql('ALTER TABLE `order` DROP provider_order_id');
    }
}
