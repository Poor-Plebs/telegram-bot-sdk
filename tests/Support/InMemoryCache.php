<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\Tests\Support;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

final class InMemoryCache implements CacheInterface
{
    /**
     * @var array<string,mixed>
     */
    private array $store = [];

    public function clear(): bool
    {
        $this->store = [];

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->store[$key]);

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete((string)$key);
        }

        return true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->has($key)) {
            return $default;
        }

        return $this->store[$key];
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get((string)$key, $default);
        }

        return $values;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->store);
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->store[$key] = $value;

        return true;
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set((string)$key, $value, $ttl);
        }

        return true;
    }
}
