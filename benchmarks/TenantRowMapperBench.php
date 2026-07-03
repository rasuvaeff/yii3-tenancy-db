<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3TenancyDb\Benchmarks;

use Rasuvaeff\Yii3Tenancy\Tenant;
use Rasuvaeff\Yii3TenancyDb\TenantRowMapper;
use Testo\Bench;

final class TenantRowMapperBench
{
    #[Bench(
        callables: [
            'sparse row' => [self::class, 'mapSparseRow'],
        ],
        calls: 100_000,
        iterations: 5,
    )]
    public static function mapFullRow(): Tenant
    {
        return (new TenantRowMapper())->map(row: [
            'id' => 'acme',
            'name' => 'Acme Inc',
            'status' => 'active',
            'attributes' => '{"plan":"pro","seats":10,"region":"eu"}',
        ]);
    }

    public static function mapSparseRow(): Tenant
    {
        return (new TenantRowMapper())->map(row: ['id' => 'acme']);
    }
}
