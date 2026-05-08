<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\Uid\Ulid;

final class Version20260324110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add public ULID identifier to order and backfill existing rows';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `order` ADD public_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:ulid)'");

        $orderIds = $this->connection->fetchFirstColumn('SELECT id FROM `order` WHERE public_id IS NULL');
        foreach ($orderIds as $orderId) {
            $this->addSql(
                'UPDATE `order` SET public_id = :public_id WHERE id = :id',
                [
                    'public_id' => (new Ulid())->toBinary(),
                    'id' => (int) $orderId,
                ],
                [
                    'public_id' => ParameterType::BINARY,
                    'id' => ParameterType::INTEGER,
                ]
            );
        }

        $this->addSql("ALTER TABLE `order` CHANGE public_id public_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)'");
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ORDER_PUBLIC_ID ON `order` (public_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_ORDER_PUBLIC_ID ON `order`');
        $this->addSql('ALTER TABLE `order` DROP public_id');
    }
}
