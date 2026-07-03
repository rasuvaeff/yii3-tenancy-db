<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3TenancyDb\Tests;

use Rasuvaeff\Yii3Tenancy\Tenant;
use Rasuvaeff\Yii3TenancyDb\CachedTenantProvider;
use Rasuvaeff\Yii3TenancyDb\Tests\Support\CountingTenantProvider;
use Rasuvaeff\Yii3TenancyDb\Tests\Support\ThrowingCache;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;

#[Test]
#[Covers(CachedTenantProvider::class)]
final class CachedTenantProviderTest
{
    public function secondLookupIsServedFromCache(): void
    {
        $inner = new CountingTenantProvider(['acme' => new Tenant(id: 'acme')]);
        $provider = new CachedTenantProvider(inner: $inner, cache: new MemorySimpleCache());

        Assert::same($provider->find('acme')?->id, 'acme');
        Assert::same($provider->find('acme')?->id, 'acme');
        Assert::same($inner->calls, 1);
    }

    public function cacheEntryUsesNamespacedKey(): void
    {
        $cache = new MemorySimpleCache();
        $provider = new CachedTenantProvider(
            inner: new CountingTenantProvider(['acme' => new Tenant(id: 'acme')]),
            cache: $cache,
        );

        $provider->find('acme');

        Assert::true($cache->has('yii3-tenancy-db.tenant.acme'));
    }

    public function missesAreNotCached(): void
    {
        $inner = new CountingTenantProvider();
        $provider = new CachedTenantProvider(inner: $inner, cache: new MemorySimpleCache());

        Assert::null($provider->find('nobody'));
        Assert::null($provider->find('nobody'));
        Assert::same($inner->calls, 2);
    }

    public function forgetDropsCachedEntry(): void
    {
        $inner = new CountingTenantProvider(['acme' => new Tenant(id: 'acme')]);
        $provider = new CachedTenantProvider(inner: $inner, cache: new MemorySimpleCache());

        $provider->find('acme');
        $provider->forget('acme');
        $provider->find('acme');

        Assert::same($inner->calls, 2);
    }

    public function cacheFailuresAreNonFatal(): void
    {
        $inner = new CountingTenantProvider(['acme' => new Tenant(id: 'acme')]);
        $provider = new CachedTenantProvider(inner: $inner, cache: new ThrowingCache());

        Assert::same($provider->find('acme')?->id, 'acme');

        $provider->forget('acme');

        Assert::same($provider->find('acme')?->id, 'acme');
        Assert::same($inner->calls, 2);
    }

    public function unexpectedCacheValueFallsBackToInner(): void
    {
        $cache = new MemorySimpleCache();
        $cache->set('yii3-tenancy-db.tenant.acme', 'garbage');
        $inner = new CountingTenantProvider(['acme' => new Tenant(id: 'acme')]);

        $provider = new CachedTenantProvider(inner: $inner, cache: $cache);

        Assert::same($provider->find('acme')?->id, 'acme');
        Assert::same($inner->calls, 1);
    }
}
