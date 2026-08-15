<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3TenancyDb\Tests;

use Closure;
use Psr\SimpleCache\CacheInterface;
use Rasuvaeff\Yii3Tenancy\TenantProvider;
use Rasuvaeff\Yii3TenancyDb\CachedTenantProvider;
use Rasuvaeff\Yii3TenancyDb\DbTenantProvider;
use Rasuvaeff\Yii3TenancyDb\TenantsTableName;
use Testo\Assert;
use Testo\Codecov\CoversNothing;
use Testo\Test;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Sqlite\Connection as SqliteConnection;
use Yiisoft\Db\Sqlite\Driver as SqliteDriver;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;

#[Test]
#[CoversNothing]
final class ConfigWiringTest
{
    public function bindsTenantProviderToDbProviderByDefault(): void
    {
        $provider = $this->resolveProvider($this->defaultParams());

        Assert::instanceOf($provider, DbTenantProvider::class);
    }

    public function wrapsProviderInCacheWhenEnabled(): void
    {
        $params = $this->defaultParams();
        $params['rasuvaeff/yii3-tenancy-db']['cache']['enabled'] = true;

        $provider = $this->resolveProvider($params);

        Assert::instanceOf($provider, CachedTenantProvider::class);
    }

    public function diDefinesOnlyItsOwnKeys(): void
    {
        // TenantsTableName is this package's own type; the core binds neither it
        // nor TenantProvider, so there is nothing for yiisoft/config to call a
        // duplicate
        Assert::same(
            array_keys($this->di($this->defaultParams())),
            [TenantsTableName::class, TenantProvider::class],
        );
    }

    public function tableNameFactoryAppliesThePrefix(): void
    {
        $params = $this->defaultParams();
        $params['rasuvaeff/yii3-tenancy-db']['table'] = 'custom_tenants';
        $params['rasuvaeff/yii3-tenancy-db']['table_prefix'] = 'rsv_';

        /** @var Closure $definition */
        $definition = $this->di($params)[TenantsTableName::class];

        /** @var TenantsTableName $table */
        $table = $definition();
        Assert::same($table->value, 'rsv_custom_tenants');
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultParams(): array
    {
        return require dirname(__DIR__) . '/config/params.php';
    }

    /**
     * @param array<string, mixed> $params
     */
    private function resolveProvider(array $params): TenantProvider
    {
        /** @var Closure $definition */
        $definition = $this->di($params)[TenantProvider::class];

        $driver = new SqliteDriver(dsn: 'sqlite::memory:');
        $db = new SqliteConnection(driver: $driver, schemaCache: new SchemaCache(psrCache: new MemorySimpleCache()));
        $container = new SimpleContainer([CacheInterface::class => new MemorySimpleCache()]);

        /** @var Closure $tableDefinition */
        $tableDefinition = $this->di($params)[TenantsTableName::class];

        /** @var TenantProvider */
        return $definition($db, $container, $tableDefinition());
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function di(array $params): array
    {
        return (static fn(array $params): array => require dirname(__DIR__) . '/config/di.php')($params);
    }
}
