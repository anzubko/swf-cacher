<?php declare(strict_types=1);

namespace SWF;

use DateInterval;
use SWF\Exception\CacherException;

class NoCacher extends AbstractCacher
{
    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     *
     * @throws CacherException
     */
    public function getMultiple(iterable $keys, mixed $default = null): array
    {
        $this->checkKeys($keys);

        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $default;
        }

        return $values;
    }

    /**
     * @inheritDoc
     *
     * @throws CacherException
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        $this->checkValues($values);

        return false;
    }

    /**
     * @inheritDoc
     *
     * @throws CacherException
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $this->checkKeys($keys);

        return false;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return false;
    }
}
