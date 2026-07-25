<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3TenancyDb\Migration;

use Rasuvaeff\Yii3TenancyDb\TenantsTableName;
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;

/**
 * Creates the tenants table read by {@see \Rasuvaeff\Yii3TenancyDb\DbTenantProvider}.
 *
 * The table name comes from {@see TenantsTableName}, which `config/di.php`
 * builds from params — one source of truth for the migration and the provider
 * alike. Register the migration by namespace:
 *
 * ```php
 * MigrationService::class => [
 *     'setSourceNamespaces()' => [['Rasuvaeff\\Yii3TenancyDb\\Migration']],
 * ],
 * ```
 *
 * @api
 */
final class M260704000000CreateTenantsTable implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    public function __construct(
        private readonly TenantsTableName $table = new TenantsTableName(),
    ) {}

    #[\Override]
    public function up(MigrationBuilder $b): void
    {
        $b->createTable(
            $this->table->value,
            [
                'id' => 'string(64) NOT NULL PRIMARY KEY',
                'name' => "string(190) NOT NULL DEFAULT ''",
                'status' => "string(20) NOT NULL DEFAULT 'active'",
                'attributes' => "text NOT NULL DEFAULT '{}'",
            ],
        );
    }

    #[\Override]
    public function down(MigrationBuilder $b): void
    {
        $b->dropTable($this->table->value);
    }
}
