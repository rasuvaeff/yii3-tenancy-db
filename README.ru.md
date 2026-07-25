# rasuvaeff/yii3-tenancy-db

[![Stable Version](https://img.shields.io/packagist/v/rasuvaeff/yii3-tenancy-db?label=stable&sort_semver=1)](https://packagist.org/packages/rasuvaeff/yii3-tenancy-db)
[![Total Downloads](https://img.shields.io/packagist/dt/rasuvaeff/yii3-tenancy-db)](https://packagist.org/packages/rasuvaeff/yii3-tenancy-db)
[![Build](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-tenancy-db/build.yml?branch=master)](https://github.com/rasuvaeff/yii3-tenancy-db/actions)
[![Static analysis](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-tenancy-db/static-analysis.yml?branch=master&label=static%20analysis)](https://github.com/rasuvaeff/yii3-tenancy-db/actions)
[![Psalm level](https://img.shields.io/badge/psalm-level%201-141F48?logo=psalm&logoColor=white)](https://github.com/rasuvaeff/yii3-tenancy-db/blob/master/psalm.xml)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/yii3-tenancy-db/php)](https://packagist.org/packages/rasuvaeff/yii3-tenancy-db)
[![License](https://img.shields.io/packagist/l/rasuvaeff/yii3-tenancy-db)](LICENSE.md)
[English version](README.md)

БД-хранилище тенантов для [rasuvaeff/yii3-tenancy](https://github.com/rasuvaeff/yii3-tenancy):
`TenantProvider` поверх таблицы `tenants` через yiisoft/db, опциональный
read-through кэш PSR-16 и готовая миграция.

> **Используете AI-ассистента?** В [llms.txt](llms.txt) — компактный
> API-справочник, которым можно поделиться с моделью. Контрибьюторам: см.
> [AGENTS.md](AGENTS.md).

## Требования

| Требование | Версия |
|-------------|---------|
| PHP | 8.3 – 8.5 |
| `rasuvaeff/yii3-tenancy` | `^1.0` |
| `yiisoft/db` | `^2.0` |
| `yiisoft/db-migration` | `^2.0` (для bundled-миграции) |

## Установка

```bash
composer require rasuvaeff/yii3-tenancy-db
```

Регистрируйте поставляемую миграцию **по namespace** — без путей в `vendor/`:

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

Имя таблицы задаётся в params — `config/di.php` превращает его в
`TenantsTableName`, который получают и миграция, и `DbTenantProvider`:

```php
// config/common/params.php
'rasuvaeff/yii3-tenancy-db' => [
    'table' => 'my_tenants',
    'table_prefix' => '',   // добавляется перед `table`; например 'rsv_' → rsv_my_tenants
],
```

> **Не настраивайте миграцию через DI-контейнер.**
> `M...::class => ['__construct()' => ['table' => ...]]` не работает: миграцию
> создаёт `Injector::make()`, который резолвит аргументы по типу и никогда не
> читает определение контейнера по имени класса самой миграции. Хуже того,
> добавление такого определения роняет контейнер на этапе сборки в **каждом**
> запросе, потому что класс не автозагружается, пока его не подключит раннер
> миграций. Этот рецепт был описан в 1.x и никогда не работал.

## Использование

При наличии `yiisoft/config` никакой связки не требуется — пакет привязывает
`TenantProvider` к `DbTenantProvider` (ядро намеренно оставляет этот интерфейс
непривязанным; установка ядра + этого бэкенда работает из коробки):

```php
use Rasuvaeff\Yii3Tenancy\CurrentTenant;

final readonly class InvoiceService
{
    public function __construct(private CurrentTenant $currentTenant) {}
    // TenantResolutionMiddleware looks tenants up through DbTenantProvider
}
```

Ручное конструирование:

```php
use Rasuvaeff\Yii3TenancyDb\CachedTenantProvider;
use Rasuvaeff\Yii3TenancyDb\DbTenantProvider;

$provider = new DbTenantProvider(db: $connection, table: 'tenants');

// optional PSR-16 read-through cache
$cached = new CachedTenantProvider(inner: $provider, cache: $psr16, ttl: 60);
$cached->forget('acme');   // drop the entry after updating/suspending a tenant
```

Семантика кэширования: кэшируются только **найденные** тенанты (новосозданный
тенант появляется сразу); ошибки чтения/записи кэша нефатальны; записи
устаревают по TTL или через явный `forget()`.

Включите кэш через params:

```php
// config/params.php
return [
    'rasuvaeff/yii3-tenancy-db' => [
        'table' => 'tenants',
        'cache' => ['enabled' => true, 'ttl' => 60],
    ],
];
```

## Схема таблицы

| Колонка | Тип | Примечания |
|---|---|---|
| `id` | `string(64)` PK | должен удовлетворять ядровому `Tenant::isValidId()` |
| `name` | `string(190)` | default `''` |
| `status` | `string(20)` | `active` (по умолчанию) / `suspended` |
| `attributes` | `text` | JSON-объект, default `'{}'` |

Невалидные строки (неизвестный status, некорректный JSON, невалидный id) бросают
`InvalidTenantRowException` — никогда не пропускаются молча и не дефолтятся.

## Компоненты

| Класс | Роль |
|---|---|
| `DbTenantProvider` | `TenantProvider` поверх yiisoft/db: однострочный `find()` по primary key |
| `CachedTenantProvider` | PSR-16 read-through декоратор (`yii3-tenancy-db.tenant.{key}`), инвалидация через `forget()` |
| `Exception\InvalidTenantRowException` | бросается внутренним row-маппером на невалидные строки |

## Безопасность

- Поиск использует bound parameters через query builder yiisoft/db — без
  SQL-строковой интерполяции.
- Имя таблицы — это конфигурация (контролируется разработчиком), а не ввод
  пользователя.
- Строки строго валидируются при чтении; повреждённая строка громко падает
  вместо того, чтобы породить полувалидного тенанта.

## Примеры

См. [examples/](examples/) — запускаемый скрипт.

| Скрипт | Показывает | Нужен сервер? |
|--------|-------|:-------------:|
| [`db-provider.php`](examples/db-provider.php) | Миграция + поиск + кэшированный поиск на in-memory SQLite | нет |

## Разработка

На хосте нет PHP/Composer — запускайте через Docker-образ `composer:2`:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
```

Или через Make: `make build`, `make cs-fix`, `make psalm`, `make test`.

## Лицензия

BSD-3-Clause. См. [LICENSE.md](LICENSE.md).
