<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3TenancyDb\Tests;

use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Gen;
use Rasuvaeff\PropertyTesting\Property;
use Rasuvaeff\Yii3Tenancy\TenantStatus;
use Rasuvaeff\Yii3TenancyDb\Exception\InvalidTenantRowException;
use Rasuvaeff\Yii3TenancyDb\TenantRowMapper;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Test;

#[Test]
#[Covers(TenantRowMapper::class)]
#[Covers(InvalidTenantRowException::class)]
final class TenantRowMapperTest
{
    public function mapsFullRow(): void
    {
        $tenant = (new TenantRowMapper())->map(row: [
            'id' => 'acme',
            'name' => 'Acme Inc',
            'status' => 'suspended',
            'attributes' => '{"plan":"pro","seats":10}',
        ]);

        Assert::same($tenant->id, 'acme');
        Assert::same($tenant->name, 'Acme Inc');
        Assert::same($tenant->status, TenantStatus::Suspended);
        Assert::same($tenant->attributes, ['plan' => 'pro', 'seats' => 10]);
    }

    public function appliesDefaultsToSparseRow(): void
    {
        $tenant = (new TenantRowMapper())->map(row: ['id' => 'acme']);

        Assert::same($tenant->name, '');
        Assert::same($tenant->status, TenantStatus::Active);
        Assert::same($tenant->attributes, []);
    }

    public function emptyAttributesStringDecodesToEmptyArray(): void
    {
        $tenant = (new TenantRowMapper())->map(row: ['id' => 'acme', 'attributes' => '']);

        Assert::same($tenant->attributes, []);
    }

    public function integerAttributeKeysAreCastToStrings(): void
    {
        $tenant = (new TenantRowMapper())->map(row: ['id' => 'acme', 'attributes' => '{"0":"zero"}']);

        Assert::same($tenant->attributes, ['0' => 'zero']);
    }

    #[Property(runs: 300)]
    public function attributesSurviveJsonRoundTrip(array $attributes): void
    {
        $tenant = (new TenantRowMapper())->map(row: [
            'id' => 'acme',
            'attributes' => json_encode($attributes, JSON_THROW_ON_ERROR),
        ]);

        Assert::same($tenant->attributes, $attributes);
    }

    /** @return array<string, ArbitraryInterface> */
    private function attributesSurviveJsonRoundTripGenerators(): array
    {
        return [
            // 'k' prefix keeps keys non-numeric: PHP canonicalizes "7" to int 7,
            // which would break strict comparison after the JSON round trip.
            'attributes' => Gen::dictOf(
                Gen::map(Gen::stringOf(0, 8), static fn(string $s): string => 'k' . $s),
                Gen::oneOf(true, false, null, 'pro', 'basic', '', 0, 42, -7, 100_000),
            ),
        ];
    }

    #[DataProvider('invalidRowProvider')]
    public function throwsOnInvalidRow(array $row): void
    {
        Expect::exception(InvalidTenantRowException::class);

        (new TenantRowMapper())->map(row: $row);
    }

    public static function invalidRowProvider(): iterable
    {
        yield 'missing id' => [['name' => 'Acme']];
        yield 'non-string id' => [['id' => 42]];
        yield 'invalid tenant id' => [['id' => 'bad id']];
        yield 'non-string name' => [['id' => 'acme', 'name' => 7]];
        yield 'non-string status' => [['id' => 'acme', 'status' => 1]];
        yield 'unknown status' => [['id' => 'acme', 'status' => 'frozen']];
        yield 'non-string attributes' => [['id' => 'acme', 'attributes' => 42]];
        yield 'malformed attributes json' => [['id' => 'acme', 'attributes' => '{broken']];
        yield 'scalar attributes json' => [['id' => 'acme', 'attributes' => '"pro"']];
    }
}
