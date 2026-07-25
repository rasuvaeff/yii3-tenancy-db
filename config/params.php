<?php

declare(strict_types=1);

return [
    'rasuvaeff/yii3-tenancy-db' => [
        // one source of truth: both DbTenantProvider and the bundled migration
        // read the resulting name through TenantsTableName
        'table' => 'tenants',
        // prepended to `table`; set it once to keep every rasuvaeff table out
        // of the way of your application's own
        'table_prefix' => '',
        'cache' => [
            'enabled' => false,
            'ttl' => 60,
        ],
    ],
];
