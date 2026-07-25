<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3TenancyDb\Tests;

use InvalidArgumentException;
use Rasuvaeff\Yii3TenancyDb\TenantsTableName;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Test;

#[Test]
#[Covers(TenantsTableName::class)]
final class TenantsTableNameTest
{
    public function defaultsToTheDocumentedName(): void
    {
        Assert::same((new TenantsTableName())->value, 'tenants');
        Assert::same((string) new TenantsTableName(), 'tenants');
    }

    public function acceptsASchemaQualifiedName(): void
    {
        Assert::same((new TenantsTableName('public.tenants'))->value, 'public.tenants');
    }

    public function indexBaseFlattensTheSchemaSeparator(): void
    {
        // a dot cannot appear in an index name
        Assert::same((new TenantsTableName('public.tenants'))->forIndexName(), 'public_tenants');
        Assert::same((new TenantsTableName('tenants'))->forIndexName(), 'tenants');
    }

    #[DataProvider('invalidNamesProvider')]
    public function rejectsAnythingOutsideTheIdentifierWhitelist(string $name): void
    {
        Expect::exception(InvalidArgumentException::class);

        new TenantsTableName($name);
    }

    public static function invalidNamesProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'starts with digit' => ['1table'];
        yield 'space' => ['my table'];
        yield 'semicolon injection' => ['t; DROP TABLE users'];
        yield 'dash' => ['my-table'];
        yield 'two dots' => ['a.b.c'];
        // PCRE's $ also matches before a trailing newline — the pattern is
        // anchored with \z so this is rejected
        yield 'trailing newline' => ["tenants\n"];
    }
}
