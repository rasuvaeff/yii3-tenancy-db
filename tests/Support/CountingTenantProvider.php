<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3TenancyDb\Tests\Support;

use Rasuvaeff\Yii3Tenancy\Tenant;
use Rasuvaeff\Yii3Tenancy\TenantProvider;

final class CountingTenantProvider implements TenantProvider
{
    public int $calls = 0;

    /**
     * @param array<string, Tenant> $tenants
     */
    public function __construct(
        private readonly array $tenants = [],
    ) {}

    #[\Override]
    public function find(string $key): ?Tenant
    {
        ++$this->calls;

        return $this->tenants[$key] ?? null;
    }
}
