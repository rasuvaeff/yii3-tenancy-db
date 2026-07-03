# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
