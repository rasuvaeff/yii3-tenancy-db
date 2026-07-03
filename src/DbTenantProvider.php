<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3TenancyDb;

use Rasuvaeff\Yii3Tenancy\Tenant;
use Rasuvaeff\Yii3Tenancy\TenantProvider;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * @api
 */
final readonly class DbTenantProvider implements TenantProvider
{
    /**
     * @param non-empty-string $table
     */
    public function __construct(
        private ConnectionInterface $db,
        private string $table = 'tenants',
    ) {}

    #[\Override]
    public function find(string $key): ?Tenant
    {
        $row = (new Query($this->db))
            ->from($this->table)
            ->where(['id' => $key])
            ->one();

        // one() is typed array|object|null; objects appear only with a custom
        // resultCallback, which this provider never sets.
        if (!is_array($row)) {
            return null;
        }

        return (new TenantRowMapper())->map(row: $row);
    }
}
