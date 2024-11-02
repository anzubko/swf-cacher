<?php
declare(strict_types=1);

namespace SWF;

use APCUIterator;
use DateInterval;
use SWF\Exception\CacherException;

class ApcCacher extends AbstractCacher
{
    protected string $ns;

    /**
     * @param string|null $ns Namespace prefix.
     * @param int|null $ttl Default TTL.
     */
    public function __construct(?string $ns = null, ?int $ttl = null)
    {
        $this->ttl = $ttl ?? $this->ttl;

        $this->ns = $ns ?? md5(__FILE__);
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = apcu_fetch($this->ns . $key, $success);

        return $success ? $value : $default;
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        return apcu_store($this->ns . $key, $value, $this->fixTtl($ttl));
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        return apcu_delete($this->ns . $key);
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
        $keys = $this->checkKeys($keys);

        $fetched = apcu_fetch(array_map(fn($key) => $this->ns . $key, $keys));
        if ($fetched === false) {
            $fetched = [];
        }

        $values = [];
        foreach ($keys as $key) {
            if (isset($this->ns, $fetched[$this->ns . $key])) {
                $values[$key] = $fetched[$this->ns . $key];
            } else {
                $values[$key] = $default;
            }
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
        $values = $this->checkValues($values);

        $values = array_combine(array_map(fn($k) => $this->ns . $k, array_keys($values)), $values);

        return !apcu_store($values, null, $this->fixTtl($ttl));
    }

    /**
     * @inheritDoc
     *
     * @throws CacherException
     */
    public function deleteMultiple(iterable $keys): bool
    {
        return apcu_delete(new APCUIterator(array_map(fn($k) => $this->ns . $k, $this->checkKeys($keys))));
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return apcu_exists($this->ns . $key);
    }
}
