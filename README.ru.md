# rasuvaeff/yii3-tenancy-db
[![Stable Version](https://img.shields.io/packagist/v/rasuvaeff/yii3-tenancy-db?label=stable&sort_semver=1)](https://packagist.org/packages/rasuvaeff/yii3-tenancy-db)
[![Total Downloads](https://img.shields.io/packagist/dt/rasuvaeff/yii3-tenancy-db)](https://packagist.org/packages/rasuvaeff/yii3-tenancy-db)
[![Build](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-tenancy-db/build.yml?branch=master)](https://github.com/rasuvaeff/yii3-tenancy-db/actions)
[![Static analysis](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-tenancy-db/static-analysis.yml?branch=master&label=static%20analysis)](https://github.com/rasuvaeff/yii3-tenancy-db/actions)
[![Psalm level](https://img.shields.io/badge/psalm-level%201-141F48?logo=psalm&logoColor=white)](https://github.com/rasuvaeff/yii3-tenancy-db/blob/master/psalm.xml)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/yii3-tenancy-db/php)](https://packagist.org/packages/rasuvaeff/yii3-tenancy-db)
[![License](https://img.shields.io/packagist/l/rasuvaeff/yii3-tenancy-db)](LICENSE.md)
Database tenant storage for [rasuvaeff/yii3-tenancy](https://github.com/rasuvaeff/yii3-tenancy):
TenantProvider, поддерживаемый таблицей tenants через yiisoft/db, дополнительным кэшем сквозного чтения PSR-16
 и готовой миграцией.

 > **Используете помощника по кодированию с использованием искусственного интеллекта?** [llms.txt](llms.txt) содержит компактную ссылку
 > API, которой вы можете поделиться с моделью. Авторы: см. [AGENTS.md](AGENTS.md). @@ЛИНИЯ@@
## Требования
| Требование | Версия |
 |-------------|---------|
 | PHP | 8,3 – 8,5 |
 | `rasuvaeff/yii3-tenancy` | `^1.0` |
 | `yiisoft/db` | `^2.0` |
 | `yiisoft/db-миграция` | `^2.0` (для комплексной миграции) | @@ЛИНИЯ@@
## Установка
```bash
composer require rasuvaeff/yii3-tenancy-db
```
Зарегистрируйте путь миграции и запустите миграцию:

```php
// config/params.php
'yiisoft/db-migration' => [
    'sourcePaths' => [dirname(__DIR__) . '/vendor/rasuvaeff/yii3-tenancy-db/migrations'],
],
```
```bash
./yii migrate:up
```
## Использование
При использовании `yiisoft/config` никаких подключений не требуется — этот пакет связывает
 `TenantProvider` с `DbTenantProvider` (ядро намеренно оставляет этот интерфейс
 несвязанным; установка ядра + этого бэкэнда просто работает):

```php
use Rasuvaeff\Yii3Tenancy\CurrentTenant;

final readonly class InvoiceService
{
    public function __construct(private CurrentTenant $currentTenant) {}
    // TenantResolutionMiddleware looks tenants up through DbTenantProvider
}
```
Ручное построение:

```php
use Rasuvaeff\Yii3TenancyDb\CachedTenantProvider;
use Rasuvaeff\Yii3TenancyDb\DbTenantProvider;

$provider = new DbTenantProvider(db: $connection, table: 'tenants');

// optional PSR-16 read-through cache
$cached = new CachedTenantProvider(inner: $provider, cache: $psr16, ttl: 60);
$cached->forget('acme');   // drop the entry after updating/suspending a tenant
```
Семантика кэширования: кэшируются только **найденные** арендаторы (вновь созданный арендатор
 появляется немедленно); сбои чтения/записи кэша не являются фатальными; срок действия записей истекает
 по TTL или явному `forget()`.

 Включите кеш через параметры:

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
| Столбец | Тип | Заметки |
 |---|---|---|
 | `идентификатор` | `строка(64)` ПК | должен соответствовать основному `Tenant::isValidId()` |
 | `имя` | `строка(190)` | по умолчанию `''` |
 | `статус` | `строка(20)` | `активный` (по умолчанию) / `приостановленный` |
 | `атрибуты` | `текст` | Объект JSON, по умолчанию `'{}'` |

 Недопустимые строки (неизвестный статус, неверный формат JSON, неверный идентификатор) выдают
 `InvalidTenantRowException` — никогда не пропускаются автоматически и не по умолчанию. @@ЛИНИЯ@@
## Компоненты
| Класс | Роль |
 |---|---|
 | `DbTenantProvider` | `TenantProvider` через yiisoft/db: однострочный `find()` по первичному ключу |
 | `CachedTenantProvider` | Декоратор сквозного чтения PSR-16 (`yii3-tenancy-db.tenant.{key}`), `forget()`, аннулирование |
 | `Exception\InvalidTenantRowException` | выдается внутренним преобразователем строк для недопустимых строк | @@ЛИНИЯ@@
## Безопасность
— При поиске используются связанные параметры через построитель запросов yiisoft/db — без интерполяции строк SQL
.
 — имя таблицы представляет собой конфигурацию (контролируется разработчиком), а не вводимые пользователем данные.
 — строки строго проверяются при чтении; поврежденная строка громко завершается с ошибкой вместо того, чтобы
 создавал полудействительный клиент. @@ЛИНИЯ@@
## Примеры
См. [examples/](examples/) для работоспособного сценария.

 | Скрипт | Шоу | Нужен сервер? |
 |--------|-------|:-------------:|
 | [`db-provider.php`](examples/db-provider.php) | Миграция + поиск + поиск в кэше в SQLite в памяти | нет | @@ЛИНИЯ@@
## Разработка
На хосте нет PHP/Composer — запустите в Docker через образ `composer:2`:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
```
Или с помощью Make: make build, make cs-fix, make psalm, make test. @@ЛИНИЯ@@
## Лицензия
BSD-3-пункт. См. [LICENSE.md](LICENSE.md).
