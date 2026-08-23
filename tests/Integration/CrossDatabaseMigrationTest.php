<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3TenancyDb\Tests\Integration;

use Rasuvaeff\Yii3TenancyDb\DbTenantProvider;
use Rasuvaeff\Yii3TenancyDb\Migration\M260704000000CreateTenantsTable;
use Testo\Assert;
use Testo\Codecov\CoversNothing;
use Testo\Test;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Mysql\Connection as MysqlConnection;
use Yiisoft\Db\Mysql\Driver as MysqlDriver;
use Yiisoft\Db\Pgsql\Connection as PgsqlConnection;
use Yiisoft\Db\Pgsql\Driver as PgsqlDriver;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;

/**
 * The bundled migration is otherwise only ever applied to SQLite, which accepts
 * DDL the other engines reject — a literal `DEFAULT` on a TEXT column aborts
 * `migrate:up` on MySQL with error 1101 and creates nothing. Runs only when
 * `TENANCY_TEST_DB` names a live server; CI supplies both.
 */
#[Test]
#[CoversNothing]
final class CrossDatabaseMigrationTest
{
    public function migrationAppliesOnConfiguredDatabase(): void
    {
        $database = getenv('TENANCY_TEST_DB');

        if ($database !== 'mysql' && $database !== 'pgsql') {
            Assert::true($database === false || $database === '');

            return;
        }

        $db = $this->connection($database);
        $db->open();

        try {
            $db->createCommand('DROP TABLE IF EXISTS tenants')->execute();

            $migration = new M260704000000CreateTenantsTable();
            $builder = new MigrationBuilder(db: $db, informer: new NullMigrationInformer());

            $migration->up($builder);

            // the package never writes this table: both inserts are the shape a
            // consumer writes, and the first one omits `attributes` entirely
            $db->createCommand()
                ->insert(table: 'tenants', columns: ['id' => 'acme', 'name' => 'Acme Inc', 'status' => 'active'])
                ->execute();
            $db->createCommand()
                ->insert(table: 'tenants', columns: [
                    'id' => 'globex',
                    'name' => 'Globex',
                    'status' => 'suspended',
                    'attributes' => '{"plan":"pro"}',
                ])
                ->execute();

            $provider = new DbTenantProvider(db: $db);

            Assert::same($provider->find('acme')?->attributes, []);
            Assert::same($provider->find('globex')?->attributes, ['plan' => 'pro']);

            $migration->down($builder);

            Assert::null($db->getTableSchema('tenants', true));
        } finally {
            $db->createCommand('DROP TABLE IF EXISTS tenants')->execute();
            $db->close();
        }
    }

    private function connection(string $database): ConnectionInterface
    {
        $cache = new SchemaCache(psrCache: new MemorySimpleCache());
        $mysqlPort = getenv('TENANCY_TEST_MYSQL_PORT') ?: '3306';
        $pgsqlPort = getenv('TENANCY_TEST_PGSQL_PORT') ?: '5432';

        return $database === 'mysql'
            ? new MysqlConnection(
                driver: new MysqlDriver(
                    dsn: sprintf('mysql:host=127.0.0.1;port=%s;dbname=tenancy;charset=utf8mb4', $mysqlPort),
                    username: 'root',
                    password: 'tenancy',
                ),
                schemaCache: $cache,
            )
            : new PgsqlConnection(
                driver: new PgsqlDriver(
                    dsn: sprintf('pgsql:host=127.0.0.1;port=%s;dbname=tenancy', $pgsqlPort),
                    username: 'postgres',
                    password: 'tenancy',
                ),
                schemaCache: $cache,
            );
    }
}
