<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3TenancyDb\Tests;

use Rasuvaeff\Yii3TenancyDb\DbTenantProvider;
use Rasuvaeff\Yii3TenancyDb\Migration\M260704000000CreateTenantsTable;
use Rasuvaeff\Yii3TenancyDb\TenantsTableName;
use Testo\Assert;
use Testo\Codecov\CoversNothing;
use Testo\Lifecycle\AfterTest;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Sqlite\Connection as SqliteConnection;
use Yiisoft\Db\Sqlite\Driver as SqliteDriver;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;

#[Test]
#[CoversNothing]
final class MigrationTest
{
    private ConnectionInterface $db;

    private MigrationBuilder $builder;

    #[BeforeTest]
    public function setUp(): void
    {
        $driver = new SqliteDriver(dsn: 'sqlite::memory:');
        $schemaCache = new SchemaCache(psrCache: new MemorySimpleCache());
        $this->db = new SqliteConnection(driver: $driver, schemaCache: $schemaCache);
        $this->db->open();

        $this->builder = new MigrationBuilder(db: $this->db, informer: new NullMigrationInformer());
    }

    #[AfterTest]
    public function tearDown(): void
    {
        $this->db->close();
    }

    public function createsAndDropsTenantsTable(): void
    {
        $migration = new M260704000000CreateTenantsTable();

        $migration->up($this->builder);

        $schema = $this->db->getTableSchema('tenants', true);
        Assert::notNull($schema);
        Assert::notNull($schema->getColumn('id'));
        Assert::notNull($schema->getColumn('name'));
        Assert::notNull($schema->getColumn('status'));
        Assert::notNull($schema->getColumn('attributes'));
        Assert::same($schema->getPrimaryKey(), ['id']);

        $migration->down($this->builder);

        Assert::null($this->db->getTableSchema('tenants', true));
    }

    public function createsTableWithCustomName(): void
    {
        (new M260704000000CreateTenantsTable(table: new TenantsTableName('custom_tenants')))->up($this->builder);

        Assert::notNull($this->db->getTableSchema('custom_tenants', true));
        Assert::null($this->db->getTableSchema('tenants', true));
    }

    /**
     * MySQL rejects a literal DEFAULT on a TEXT column with error 1101, which
     * aborted `migrate:up` before the table existed. SQLite tolerates it, so
     * only an assertion on the column itself keeps the default from returning.
     */
    public function attributesColumnCarriesNoLiteralDefault(): void
    {
        (new M260704000000CreateTenantsTable())->up($this->builder);

        $attributes = $this->db->getTableSchema('tenants', true)?->getColumn('attributes');

        Assert::notNull($attributes);
        Assert::null($attributes->getDefaultValue());
        Assert::notSame($attributes->isNotNull(), true);
    }

    public function rowWithoutAttributesReadsAsAnEmptyAttributeSet(): void
    {
        (new M260704000000CreateTenantsTable())->up($this->builder);

        $this->db->createCommand(
            sql: "INSERT INTO tenants (id, name, status) VALUES ('acme', 'Acme Inc', 'active')",
        )->execute();

        $tenant = (new DbTenantProvider(db: $this->db))->find('acme');

        Assert::same($tenant?->attributes, []);
    }

    public function migratedTableIsReadableByProvider(): void
    {
        (new M260704000000CreateTenantsTable())->up($this->builder);

        $this->db->createCommand(
            sql: "INSERT INTO tenants (id, name, status, attributes)
                  VALUES ('acme', 'Acme Inc', 'active', '{\"plan\":\"pro\"}')",
        )->execute();

        $tenant = (new DbTenantProvider(db: $this->db))->find('acme');

        Assert::same($tenant?->name, 'Acme Inc');
        Assert::same($tenant?->attributes, ['plan' => 'pro']);
    }
}
