<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3TenancyDb\Tests;

use M260704000000CreateTenantsTable;
use Rasuvaeff\Yii3TenancyDb\DbTenantProvider;
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
        require_once dirname(__DIR__) . '/migrations/M260704000000CreateTenantsTable.php';

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
        (new M260704000000CreateTenantsTable(table: 'custom_tenants'))->up($this->builder);

        Assert::notNull($this->db->getTableSchema('custom_tenants', true));
        Assert::null($this->db->getTableSchema('tenants', true));
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
