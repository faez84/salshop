<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260325140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add promotions table and persist promotion discount details on orders';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE promotion (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(64) NOT NULL, type VARCHAR(20) NOT NULL, value DOUBLE PRECISION NOT NULL, active TINYINT(1) NOT NULL, valid_from DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', valid_until DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', usage_limit INT DEFAULT NULL, used_count INT NOT NULL, minimum_basket_cost DOUBLE PRECISION DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_PROMOTION_CODE (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `order` ADD promotion_code VARCHAR(64) DEFAULT NULL, ADD discount_amount DOUBLE PRECISION NOT NULL DEFAULT 0');
        $this->addSql('CREATE INDEX IDX_ORDER_PROMOTION_CODE ON `order` (promotion_code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_ORDER_PROMOTION_CODE ON `order`');
        $this->addSql('ALTER TABLE `order` DROP promotion_code, DROP discount_amount');
        $this->addSql('DROP TABLE promotion');
    }
}
