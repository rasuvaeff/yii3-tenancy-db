<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;

/**
 * Creates the tenants table read by {@see \Rasuvaeff\Yii3TenancyDb\DbTenantProvider}.
 *
 * The table name defaults to `tenants` and must match the `table` argument of
 * {@see \Rasuvaeff\Yii3TenancyDb\DbTenantProvider}. To use a custom name, bind
 * the constructor argument in your DI configuration:
 *
 * ```php
 * M260704000000CreateTenantsTable::class => [
 *     '__construct()' => ['table' => 'my_tenants'],
 * ],
 * ```
 */
final class M260704000000CreateTenantsTable implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    /**
     * @param non-empty-string $table
     */
    public function __construct(
        private readonly string $table = 'tenants',
    ) {}

    #[\Override]
    public function up(MigrationBuilder $b): void
    {
        $b->createTable(
            $this->table,
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
        $b->dropTable($this->table);
    }
}
