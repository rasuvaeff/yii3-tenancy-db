# AGENTS.md — yii3-tenancy-db

Guidance for AI agents working on this package. Read before changing code.

## What this is

Database-backed tenant storage for Yii3 applications. Implements
`TenantProvider` from `rasuvaeff/yii3-tenancy` core: `DbTenantProvider` reads
a single row by primary key via the yiisoft/db `Query` builder and maps it to
a core `Tenant` through the `@internal TenantRowMapper`.
`CachedTenantProvider` is a PSR-16 read-through decorator with TTL and
explicit `forget()` invalidation. A migration for `yiisoft/db-migration`
ships in `src/Migration/`. Namespace: `Rasuvaeff\Yii3TenancyDb`.

Public API: `DbTenantProvider`, `CachedTenantProvider`,
`Exception\InvalidTenantRowException`. `TenantRowMapper` is `@internal`.

## Golden rules

1. **Verification is mandatory.** Never claim "done" without a fresh green
   `composer build`. "Should work" does not count.
2. **No suppressions.** No `@psalm-suppress`, no baseline. Fix the root cause.
3. **Invalid row = exception.** Never silently skip or default an invalid DB
   row (unknown status, malformed attributes JSON, invalid tenant id). Throw
   `InvalidTenantRowException` with a descriptive message — a half-valid
   tenant is a security hazard (wrong isolation scope).
4. **Preserve the public contract.** Update README + tests with any API change.

## Commands

No PHP/Composer on the host — run in Docker via the `composer:2` image.

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
docker run --rm -v "$PWD":/app -w /app composer:2 composer cs:fix
docker run --rm -v "$PWD":/app -w /app composer:2 composer psalm
docker run --rm -v "$PWD":/app -w /app composer:2 composer test
docker run --rm -v "$PWD":/app -w /app composer:2 composer release-check
```

Or with Make: `make build`, `make cs-fix`, `make psalm`, `make test`,
`make test-coverage`, `make mutation`, `make release-check`.

## Invariants & gotchas

- **The table name is a VO, not a string, because `Injector` cannot resolve a
  scalar.** `yiisoft/db-migration` builds migrations via `Injector::make()`,
  which resolves arguments by name or by type and never reads a container
  definition keyed by the migration's own class. That is why the 1.x recipe
  `M...::class => ['__construct()' => ['table' => …]]` silently did nothing —
  and why adding it made `Yiisoft\Di\Container` fatal at build time. Never
  reintroduce a scalar `string $table` on a migration.
- **One source of truth for the name.** `config/di.php` builds `TenantsTableName`
  from `table_prefix` + `table` params and passes it to both the provider and
  the migration.
- Migrations live in `src/Migration/` and are therefore covered by cs, psalm and
  infection. `MigrationTableNameTest` asserts the column set.
- `composer test` runs only the Unit suite; `composer mutation` runs every
  suite. An integration test left pointing at `migrations/` passes the first and
  fails the second.
- Identifier patterns are anchored with `\z`, not `$`.
- **This backend is the ONE source binding `TenantProvider`** in
  `config/di.php` (core deliberately binds nothing) — one key, one vendor, no
  `yiisoft/config` `Duplicate key`. `ConfigWiringTest` guards the shape.
- **Path-repo during pre-publish dev:** `composer.json` carries a temporary
  `repositories` path entry pointing at `../yii3-tenancy` while the core is
  unpublished. **Remove it (and `composer update`) when publishing**, after
  the core lands on Packagist.
- `find()` is a single-row lookup by primary key — no full-table scans.
- Caching: only found tenants are cached (misses always hit the DB so new
  tenants appear immediately); cache read/write failures are non-fatal;
  invalidation by TTL or `forget()`. Cache key: `yii3-tenancy-db.tenant.{key}`
  (dots, not colons — PSR-16 reserves `:`).
- SQLite-backed provider tests live in the **Unit** suite with
  `#[Covers(DbTenantProvider::class)]` — in-memory SQLite needs no service,
  and `#[CoversNothing]` integration tests generate ZERO mutants (known Testo
  gap). Do not move them to `tests/Integration`.
- Migrations are `Rasuvaeff\Yii3TenancyDb\Migration` classes in `src/Migration/`;
  the custom table name is a constructor argument bound in DI.
  `setSourceNamespaces()` does NOT find them on any released
  `yiisoft/db-migration` (≤ 2.0.1): it matches the PSR-4 map by string prefix,
  so the namespace resolves into the core package and discovery silently finds
  zero — `migrate:up` exits 0 having created nothing. Until an upstream release
  carries the fix, migrations are applied directly via
  `Injector::make($class)->up($builder)` — see the README.
- Code: `declare(strict_types=1)`, `final readonly class`, `#[\Override]`,
  explicit types.
- `examples/` is part of the public contract: keep scripts runnable and update
  `examples/README.md` when example usage changes.
- **CI workflows are SHA-pinned.** Every `uses:` references a 40-char commit
  SHA with a `# vN` comment; `permissions: { contents: read }`,
  `persist-credentials: false` on every checkout. Never revert to floating
  tags; verify with `zizmor --persona=auditor .github/`.

## When you finish

- Update `README.md` (and `examples/` if usage changed); update `CHANGELOG.md`
  when releasing.
- Re-run `composer build`; if the change affects public API or release safety,
  also run `make release-check`. Paste the output.
