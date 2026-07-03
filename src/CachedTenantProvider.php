<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3TenancyDb;

use Psr\SimpleCache\CacheInterface;
use Rasuvaeff\Yii3Tenancy\Tenant;
use Rasuvaeff\Yii3Tenancy\TenantProvider;
use Throwable;

/**
 * PSR-16 per-tenant read-through cache. Only found tenants are cached
 * (misses always hit the inner provider so a newly created tenant appears
 * immediately). Cache read/write failures are non-fatal — the tenant is
 * still returned from the inner provider.
 *
 * @api
 */
final readonly class CachedTenantProvider implements TenantProvider
{
    public function __construct(
        private TenantProvider $inner,
        private CacheInterface $cache,
        private int $ttl = 60,
    ) {}

    #[\Override]
    public function find(string $key): ?Tenant
    {
        try {
            /** @var mixed $cached */
            $cached = $this->cache->get($this->cacheKey($key));
        } catch (Throwable) {
            $cached = null;
        }

        if ($cached instanceof Tenant) {
            return $cached;
        }

        $tenant = $this->inner->find($key);

        if ($tenant !== null) {
            try {
                $this->cache->set($this->cacheKey($key), $tenant, $this->ttl);
            } catch (Throwable) {
                // non-fatal: serving the tenant matters more than caching it
            }
        }

        return $tenant;
    }

    /**
     * Drops the cached entry — call after updating or suspending a tenant.
     */
    public function forget(string $key): void
    {
        try {
            $this->cache->delete($this->cacheKey($key));
        } catch (Throwable) {
            // non-fatal
        }
    }

    private function cacheKey(string $key): string
    {
        return 'yii3-tenancy-db.tenant.' . $key;
    }
}
