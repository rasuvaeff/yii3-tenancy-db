<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3TenancyDb\Tests\Integration;

use Rasuvaeff\Yii3Tenancy\TenantStatus;
use Rasuvaeff\Yii3TenancyDb\CachedTenantProvider;
use Rasuvaeff\Yii3TenancyDb\DbTenantProvider;
use Rasuvaeff\Yii3TenancyDb\Migration\M260704000000CreateTenantsTable;
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

/**
 * End-to-end over a real SQLite database created by the shipped migration:
 * the full storage stack (migration → DbTenantProvider → CachedTenantProvider)
 * is exercised against the actual schema, not a hand-written CREATE TABLE.
 */
#[Test]
#[CoversNothing]
final class SqliteIntegrationTest
{
    private ConnectionInterface $db;

    #[BeforeTest]
    public function setUp(): void
    {
        $driver = new SqliteDriver(dsn: 'sqlite::memory:');
        $schemaCache = new SchemaCache(psrCache: new MemorySimpleCache());
        $this->db = new SqliteConnection(driver: $driver, schemaCache: $schemaCache);
        $this->db->open();

        (new M260704000000CreateTenantsTable())->up(
            new MigrationBuilder(db: $this->db, informer: new NullMigrationInformer()),
        );
    }

    #[AfterTest]
    public function tearDown(): void
    {
        $this->db->close();
    }

    public function resolvesTenantsFromMigratedSchema(): void
    {
        $this->insert(id: 'acme', name: 'Acme Inc', status: 'active', attributes: '{"plan":"pro"}');
        $this->insert(id: 'globex', name: 'Globex', status: 'suspended', attributes: '{}');

        $provider = new DbTenantProvider(db: $this->db);

        $acme = $provider->find('acme');
        Assert::same($acme?->id, 'acme');
        Assert::same($acme?->name, 'Acme Inc');
        Assert::same($acme?->status, TenantStatus::Active);
        Assert::same($acme?->attributes, ['plan' => 'pro']);

        Assert::same($provider->find('globex')?->status, TenantStatus::Suspended);
        Assert::null($provider->find('missing'));
    }

    public function cachedProviderServesStaleRowThenInvalidatesOnForget(): void
    {
        $this->insert(id: 'acme', name: 'Acme Inc', status: 'active', attributes: '{}');

        $provider = new CachedTenantProvider(
            inner: new DbTenantProvider(db: $this->db),
            cache: new MemorySimpleCache(),
            ttl: 60,
        );

        Assert::same($provider->find('acme')?->name, 'Acme Inc');

        // Row gone from the DB, but the cached copy is still served.
        $this->db->createCommand()->delete('tenants', ['id' => 'acme'])->execute();
        Assert::same($provider->find('acme')?->name, 'Acme Inc');

        // Explicit invalidation drops the cache; the now-empty DB yields null.
        $provider->forget('acme');
        Assert::null($provider->find('acme'));
    }

    private function insert(string $id, string $name, string $status, string $attributes): void
    {
        $this->db->createCommand()->insert('tenants', [
            'id' => $id,
            'name' => $name,
            'status' => $status,
            'attributes' => $attributes,
        ])->execute();
    }
}
