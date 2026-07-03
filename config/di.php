<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use Rasuvaeff\Yii3Tenancy\TenantProvider;
use Rasuvaeff\Yii3TenancyDb\CachedTenantProvider;
use Rasuvaeff\Yii3TenancyDb\DbTenantProvider;
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var array $params */

// This backend is the ONE source binding TenantProvider (the core deliberately
// does not bind it) — installing core + this package works without app config.

return [
    TenantProvider::class => static function (
        ConnectionInterface $db,
        ContainerInterface $container,
    ) use ($params): TenantProvider {
        $config = $params['rasuvaeff/yii3-tenancy-db'] ?? [];

        $provider = new DbTenantProvider(
            db: $db,
            table: $config['table'] ?? 'tenants',
        );

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
