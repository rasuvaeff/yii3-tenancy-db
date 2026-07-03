<?php

declare(strict_types=1);

return [
    'rasuvaeff/yii3-tenancy-db' => [
        'table' => 'tenants',
        'cache' => [
            'enabled' => false,
            'ttl' => 60,
        ],
    ],
];
