# rasuvaeff/yii3-tenancy-db

[![Stable Version](https://img.shields.io/packagist/v/rasuvaeff/yii3-tenancy-db?label=stable&sort_semver=1)](https://packagist.org/packages/rasuvaeff/yii3-tenancy-db)
[![Total Downloads](https://img.shields.io/packagist/dt/rasuvaeff/yii3-tenancy-db)](https://packagist.org/packages/rasuvaeff/yii3-tenancy-db)
[![Build](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-tenancy-db/build.yml?branch=master)](https://github.com/rasuvaeff/yii3-tenancy-db/actions)
[![Static analysis](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-tenancy-db/static-analysis.yml?branch=master&label=static%20analysis)](https://github.com/rasuvaeff/yii3-tenancy-db/actions)
[![Psalm level](https://img.shields.io/badge/psalm-level%201-141F48?logo=psalm&logoColor=white)](https://github.com/rasuvaeff/yii3-tenancy-db/blob/master/psalm.xml)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/yii3-tenancy-db/php)](https://packagist.org/packages/rasuvaeff/yii3-tenancy-db)
[![License](https://img.shields.io/packagist/l/rasuvaeff/yii3-tenancy-db)](LICENSE.md)
[Русская версия](README.ru.md)

Database tenant storage for [rasuvaeff/yii3-tenancy](https://github.com/rasuvaeff/yii3-tenancy):
`TenantProvider` backed by a `tenants` table via yiisoft/db, an optional PSR-16
read-through cache, and a ready-made migration.

> **Using an AI coding assistant?** [llms.txt](llms.txt) contains a compact
> API reference you can share with the model. Contributors: see [AGENTS.md](AGENTS.md).

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | 8.3 – 8.5 |
| `rasuvaeff/yii3-tenancy` | `^1.0` |
| `yiisoft/db` | `^2.0` |
| `yiisoft/db-migration` | `^2.0` (for the bundled migration) |

## Installation

```bash
composer require rasuvaeff/yii3-tenancy-db
```

Register the bundled migration **by namespace** — no vendor paths:

```php
// config/common/di/migration.php
use Yiisoft\Db\Migration\Service\MigrationService;

return [
    MigrationService::class => [
        'setSourceNamespaces()' => [['App\\Migration', 'Rasuvaeff\\Yii3TenancyDb\\Migration']],
    ],
];
```

```bash
./yii migrate:up
```

Set the table name in params — `config/di.php` turns it into a
`TenantsTableName` that reaches the migration **and** `DbTenantProvider`:

```php
// config/common/params.php
'rasuvaeff/yii3-tenancy-db' => [
    'table' => 'my_tenants',
    'table_prefix' => '',   // prepended to `table`; e.g. 'rsv_' → rsv_my_tenants
],
```

> **Do not configure the migration through the DI container.**
> `M...::class => ['__construct()' => ['table' => ...]]` does not work: the
> migration is built by `Injector::make()`, which resolves arguments by type
> and never reads a container definition keyed by the migration's own class.
> Worse, adding that definition makes the container fatal at build time in
> **every** request, because the class is not autoloadable until the migration
> runner requires it. That recipe was documented in 1.x; it never worked.

## Usage

With `yiisoft/config` no wiring is needed — this package binds
`TenantProvider` to `DbTenantProvider` (the core deliberately leaves that
interface unbound; installing core + this backend just works):

```php
use Rasuvaeff\Yii3Tenancy\CurrentTenant;

final readonly class InvoiceService
{
    public function __construct(private CurrentTenant $currentTenant) {}
    // TenantResolutionMiddleware looks tenants up through DbTenantProvider
}
```

Manual construction:

```php
use Rasuvaeff\Yii3TenancyDb\CachedTenantProvider;
use Rasuvaeff\Yii3TenancyDb\DbTenantProvider;

$provider = new DbTenantProvider(db: $connection, table: 'tenants');

// optional PSR-16 read-through cache
$cached = new CachedTenantProvider(inner: $provider, cache: $psr16, ttl: 60);
$cached->forget('acme');   // drop the entry after updating/suspending a tenant
```

Caching semantics: only **found** tenants are cached (a newly created tenant
appears immediately); cache read/write failures are non-fatal; entries expire
by TTL or explicit `forget()`.

Enable the cache through params:

```php
// config/params.php
return [
    'rasuvaeff/yii3-tenancy-db' => [
        'table' => 'tenants',
        'cache' => ['enabled' => true, 'ttl' => 60],
    ],
];
```

## Table schema

| Column | Type | Notes |
|---|---|---|
| `id` | `string(64)` PK | must satisfy core `Tenant::isValidId()` |
| `name` | `string(190)` | default `''` |
| `status` | `string(20)` | `active` (default) / `suspended` |
| `attributes` | `text` | JSON object, default `'{}'` |

Invalid rows (unknown status, malformed JSON, invalid id) throw
`InvalidTenantRowException` — never silently skipped or defaulted.

## Components

| Class | Role |
|---|---|
| `DbTenantProvider` | `TenantProvider` over yiisoft/db: single-row `find()` by primary key |
| `CachedTenantProvider` | PSR-16 read-through decorator (`yii3-tenancy-db.tenant.{key}`), `forget()` invalidation |
| `Exception\InvalidTenantRowException` | thrown by the internal row mapper on invalid rows |

## Security

- Lookups use bound parameters via the yiisoft/db query builder — no SQL
  string interpolation.
- The table name is configuration (developer-controlled), not user input.
- Rows are strictly validated on read; a corrupted row fails loudly instead of
  producing a half-valid tenant.

## Examples

See [examples/](examples/) for a runnable script.

| Script | Shows | Needs server? |
|--------|-------|:-------------:|
| [`db-provider.php`](examples/db-provider.php) | Migration + lookup + cached lookup on in-memory SQLite | no |

## Development

No PHP/Composer on the host — run in Docker via the `composer:2` image:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
```

Or with Make: `make build`, `make cs-fix`, `make psalm`, `make test`.

## License

BSD-3-Clause. See [LICENSE.md](LICENSE.md).
