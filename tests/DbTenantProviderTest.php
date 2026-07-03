<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3TenancyDb\Tests;

use Rasuvaeff\Yii3Tenancy\TenantStatus;
use Rasuvaeff\Yii3TenancyDb\DbTenantProvider;
use Rasuvaeff\Yii3TenancyDb\Exception\InvalidTenantRowException;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Lifecycle\AfterTest;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Sqlite\Connection as SqliteConnection;
use Yiisoft\Db\Sqlite\Driver as SqliteDriver;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;

#[Test]
#[Covers(DbTenantProvider::class)]
final class DbTenantProviderTest
{
    private ConnectionInterface $db;

    #[BeforeTest]
    public function setUp(): void
    {
        $driver = new SqliteDriver(dsn: 'sqlite::memory:');
        $schemaCache = new SchemaCache(psrCache: new MemorySimpleCache());
        $this->db = new SqliteConnection(driver: $driver, schemaCache: $schemaCache);
        $this->db->open();

        $this->db->createCommand(sql: "
            CREATE TABLE tenants (
                id         VARCHAR(64)  PRIMARY KEY,
                name       VARCHAR(190) NOT NULL DEFAULT '',
                status     VARCHAR(20)  NOT NULL DEFAULT 'active',
                attributes TEXT         NOT NULL DEFAULT '{}'
            )
        ")->execute();
    }

    #[AfterTest]
    public function tearDown(): void
    {
        $this->db->close();
    }

    public function findsStoredTenant(): void
    {
        $this->insert(id: 'acme', name: 'Acme Inc', status: 'active', attributes: '{"plan":"pro"}');

        $tenant = (new DbTenantProvider(db: $this->db))->find('acme');

        Assert::same($tenant?->id, 'acme');
        Assert::same($tenant?->name, 'Acme Inc');
        Assert::same($tenant?->status, TenantStatus::Active);
        Assert::same($tenant?->attributes, ['plan' => 'pro']);
    }

    public function findsSuspendedTenant(): void
    {
        $this->insert(id: 'globex', name: 'Globex', status: 'suspended', attributes: '{}');

        Assert::same((new DbTenantProvider(db: $this->db))->find('globex')?->status, TenantStatus::Suspended);
    }

    public function returnsNullForUnknownKey(): void
    {
        Assert::null((new DbTenantProvider(db: $this->db))->find('nobody'));
    }

    public function readsFromCustomTable(): void
    {
        $this->db->createCommand(sql: "
            CREATE TABLE custom_tenants (
                id         VARCHAR(64)  PRIMARY KEY,
                name       VARCHAR(190) NOT NULL DEFAULT '',
                status     VARCHAR(20)  NOT NULL DEFAULT 'active',
                attributes TEXT         NOT NULL DEFAULT '{}'
            )
        ")->execute();
        $this->db->createCommand(sql: "INSERT INTO custom_tenants (id) VALUES ('acme')")->execute();

        $provider = new DbTenantProvider(db: $this->db, table: 'custom_tenants');

        Assert::same($provider->find('acme')?->id, 'acme');
        Assert::null((new DbTenantProvider(db: $this->db))->find('acme'));
    }

    public function throwsOnInvalidStoredRow(): void
    {
        $this->insert(id: 'acme', name: 'Acme', status: 'frozen', attributes: '{}');

        Expect::exception(InvalidTenantRowException::class);

        (new DbTenantProvider(db: $this->db))->find('acme');
    }

    private function insert(string $id, string $name, string $status, string $attributes): void
    {
        $this->db->createCommand()
            ->insert(table: 'tenants', columns: [
                'id' => $id,
                'name' => $name,
                'status' => $status,
                'attributes' => $attributes,
            ])
            ->execute();
    }
}
