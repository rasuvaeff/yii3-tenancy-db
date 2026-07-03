# Examples

| Script | Shows | Needs server? |
|--------|-------|:-------------:|
| [`db-provider.php`](db-provider.php) | Bundled migration, `DbTenantProvider` lookup, `CachedTenantProvider` read-through + `forget()` on in-memory SQLite | no |

Run from the package root (after `composer install`):

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 php examples/db-provider.php
```
