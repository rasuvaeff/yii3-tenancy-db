<?php

declare(strict_types=1);

use Rasuvaeff\Yii3TenancyDb\CachedTenantProvider;
use Rasuvaeff\Yii3TenancyDb\DbTenantProvider;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Sqlite\Connection as SqliteConnection;
use Yiisoft\Db\Sqlite\Driver as SqliteDriver;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/migrations/M260704000000CreateTenantsTable.php';

$db = new SqliteConnection(
    driver: new SqliteDriver(dsn: 'sqlite::memory:'),
    schemaCache: new SchemaCache(psrCache: new MemorySimpleCache()),
);
$db->open();

// 1. Run the bundled migration
(new M260704000000CreateTenantsTable())->up(new MigrationBuilder(db: $db, informer: new NullMigrationInformer()));
echo "migrated -> tenants table created\n";

// 2. Seed and look up
$db->createCommand()->insert(table: 'tenants', columns: [
    'id' => 'acme',
    'name' => 'Acme Inc',
    'status' => 'active',
    'attributes' => '{"plan":"pro"}',
])->execute();

$provider = new DbTenantProvider(db: $db);
$tenant = $provider->find('acme');
echo "db lookup -> {$tenant?->id} ({$tenant?->name}), plan={$tenant?->attributes['plan']}\n";
echo 'unknown  -> ' . var_export($provider->find('nobody'), true) . "\n";

// 3. Read-through cache
$cached = new CachedTenantProvider(inner: $provider, cache: new MemorySimpleCache(), ttl: 60);
$cached->find('acme');
$db->createCommand()->delete(table: 'tenants', condition: ['id' => 'acme'])->execute();
echo "cached   -> {$cached->find('acme')?->id} (served from cache after row deletion)\n";

$cached->forget('acme');
echo 'forgotten -> ' . var_export($cached->find('acme'), true) . "\n";

$db->close();
