# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 2.0.1 — 2026-08-01

- Docs: the documented `setSourceNamespaces()` migration registration does not
  find the bundled migration and never has — `yiisoft/db-migration` matches the
  PSR-4 map by string prefix and resolves into the core package, so
  `./yii migrate:up` exits 0 having created nothing. Both READMEs now say so and
  give a working `Injector`-based recipe until the upstream fix ships.

## 2.0.0 — 2026-07-25

**Breaking.** See [UPGRADE.md](UPGRADE.md) — an installation that already
applied the migration must rewrite one row in the `migration` table.

- The bundled migration moved to `Rasuvaeff\Yii3TenancyDb\Migration\M260704000000CreateTenantsTable`
  (`src/Migration/`, PSR-4 autoloaded) from a global class in `migrations/`.
  Register it with `setSourceNamespaces()` instead of a `vendor/` path. Being
  autoloadable is what makes it safe to reference in DI at all: with the old
  global class, adding any container definition for it made
  `Yiisoft\Di\Container` fatal at build time in every request, because
  `new ReflectionClass()` ran before the migration runner had required the file.
- **The documented way to rename the table never worked.**
  `M...::class => ['__construct()' => ['table' => ...]]` is ignored:
  `yiisoft/db-migration` builds migrations through `Injector::make()`, which
  resolves arguments by name or type from the container and does not read
  definitions keyed by the migration's class — and a scalar `string $table` has
  no type to resolve. Users following the README silently got the default name.
- The table name is now a typed value object that `Injector` *can* resolve,
  built by `config/di.php` from params. One source of truth: the migration and
  `DbTenantProvider` cannot disagree any more (in 1.x the runtime read params while the
  migration used its own default, so configuring params pointed the runtime at a
  table the migration had never created).
- New `table_prefix` param, prepended to `table` — a single place to keep
  package tables out of the way of an application's own.
- `DbTenantProvider` validates the table name (through the same value object) —
  in 1.x it interpolated whatever string it was given straight into the query
  builder, with no identifier check at all.
- Bump `rasuvaeff/property-testing` to `^2.6`.


## 1.0.0 — 2026-07-04

- Initial release: database-backed tenant storage for `rasuvaeff/yii3-tenancy`.
- `DbTenantProvider` implements the core `TenantProvider`, reading a single row
  by primary key via the yiisoft/db `Query` builder and mapping it to a
  `Tenant`.
- `CachedTenantProvider` — PSR-16 read-through decorator with TTL and explicit
  `forget()` invalidation.
- `Exception\InvalidTenantRowException` for malformed tenant rows.
- Ships a `yiisoft/db-migration` migration (`M260704000000CreateTenantsTable`)
  for the tenants table.
