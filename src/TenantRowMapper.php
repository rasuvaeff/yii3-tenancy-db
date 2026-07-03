<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3TenancyDb;

use InvalidArgumentException;
use JsonException;
use Rasuvaeff\Yii3Tenancy\Tenant;
use Rasuvaeff\Yii3Tenancy\TenantStatus;
use Rasuvaeff\Yii3TenancyDb\Exception\InvalidTenantRowException;

/**
 * Maps a `tenants` table row to a {@see Tenant}. Invalid rows throw —
 * never silently skipped or defaulted.
 *
 * @internal
 */
final readonly class TenantRowMapper
{
    /**
     * @param array<array-key, mixed> $row
     */
    public function map(array $row): Tenant
    {
        $id = $row['id'] ?? null;

        if (!is_string($id)) {
            throw new InvalidTenantRowException('Tenant row is missing a string "id" column');
        }

        $name = $row['name'] ?? '';

        if (!is_string($name)) {
            throw new InvalidTenantRowException(sprintf('Tenant "%s" has a non-string "name" column', $id));
        }

        $status = $row['status'] ?? TenantStatus::Active->value;

        if (!is_string($status)) {
            throw new InvalidTenantRowException(sprintf('Tenant "%s" has a non-string "status" column', $id));
        }

        try {
            return new Tenant(
                id: $id,
                name: $name,
                status: TenantStatus::tryFrom($status)
                    ?? throw new InvalidTenantRowException(sprintf('Tenant "%s" has unknown status "%s"', $id, $status)),
                attributes: $this->decodeAttributes(id: $id, raw: $row['attributes'] ?? '{}'),
            );
        } catch (InvalidArgumentException $e) {
            throw new InvalidTenantRowException(sprintf('Tenant row "%s" is invalid: %s', $id, $e->getMessage()), previous: $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeAttributes(string $id, mixed $raw): array
    {
        if (!is_string($raw)) {
            throw new InvalidTenantRowException(sprintf('Tenant "%s" has a non-string "attributes" column', $id));
        }

        try {
            $decoded = json_decode($raw === '' ? '{}' : $raw, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidTenantRowException(sprintf('Tenant "%s" has malformed attributes JSON', $id), previous: $e);
        }

        if (!is_array($decoded)) {
            throw new InvalidTenantRowException(sprintf('Tenant "%s" attributes JSON must decode to an object', $id));
        }

        $attributes = [];
        /** @var mixed $value */
        foreach ($decoded as $key => $value) {
            /** @var mixed */
            $attributes[(string) $key] = $value;
        }

        return $attributes;
    }
}
