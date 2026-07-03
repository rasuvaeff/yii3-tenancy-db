<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3TenancyDb\Tests\Support;

use DateInterval;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

final readonly class ThrowingCache implements CacheInterface
{
    #[\Override]
    public function get(string $key, mixed $default = null): mixed
    {
        throw new RuntimeException('cache down');
    }

    #[\Override]
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        throw new RuntimeException('cache down');
    }

    #[\Override]
    public function delete(string $key): bool
    {
        throw new RuntimeException('cache down');
    }

    #[\Override]
    public function clear(): bool
    {
        throw new RuntimeException('cache down');
    }

    #[\Override]
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        throw new RuntimeException('cache down');
    }

    #[\Override]
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        throw new RuntimeException('cache down');
    }

    #[\Override]
    public function deleteMultiple(iterable $keys): bool
    {
        throw new RuntimeException('cache down');
    }

    #[\Override]
    public function has(string $key): bool
    {
        throw new RuntimeException('cache down');
    }
}
