<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use Rasuvaeff\Yii3Tenancy\TenantProvider;
use Rasuvaeff\Yii3TenancyDb\CachedTenantProvider;
use Rasuvaeff\Yii3TenancyDb\DbTenantProvider;
use Rasuvaeff\Yii3TenancyDb\TenantsTableName;
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var array $params */

// This backend is the ONE source binding TenantProvider (the core deliberately
// does not bind it) — installing core + this package works without app config.

return [
    // the migration resolves this by type through Injector::make(), so the
    // provider and the migration can never disagree about the table
    TenantsTableName::class => static function () use ($params): TenantsTableName {
        $config = $params['rasuvaeff/yii3-tenancy-db'] ?? [];

        return new TenantsTableName(
            ((string) ($config['table_prefix'] ?? '')) . ((string) ($config['table'] ?? 'tenants')),
        );
    },
    TenantProvider::class => static function (
        ConnectionInterface $db,
        ContainerInterface $container,
        TenantsTableName $table,
    ) use ($params): TenantProvider {
        $config = $params['rasuvaeff/yii3-tenancy-db'] ?? [];

        $provider = new DbTenantProvider(db: $db, table: $table->value);

        $cacheConfig = $config['cache'] ?? [];

        if (($cacheConfig['enabled'] ?? false) === true) {
            return new CachedTenantProvider(
                inner: $provider,
                cache: $container->get(CacheInterface::class),
                ttl: $cacheConfig['ttl'] ?? 60,
            );
        }

        return $provider;
    },
];
