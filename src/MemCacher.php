<?php declare(strict_types=1);

namespace SWF;

use DateInterval;
use Memcached;
use SWF\Exception\CacherException;

class MemCacher extends AbstractCacher
{
    protected Memcached $memcached;

    /**
     * @param string|null $ns Namespace prefix.
     * @param int|null $ttl Default TTL.
     * @param array<mixed[]>|null $servers Servers to connect.
     * @param mixed[]|null $options Memcached options.
     */
    public function __construct(?string $ns = null, ?int $ttl = 0, ?array $servers = [], ?array $options = [])
    {
        if (!extension_loaded('memcached')) {
            return;
        }

        $this->ttl = $ttl ?? $this->ttl;

        if (empty($servers)) {
            $servers = [['127.0.0.1', 11211]];
        }

        $options[Memcached::OPT_PREFIX_KEY] = $ns ?? md5(__FILE__);

        $this->memcached = new Memcached();
        $this->memcached->addServers($servers);
        $this->memcached->setOptions($options);
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!isset($this->memcached)) {
            return $default;
        }

        $values = $this->memcached->getMulti([$key]);

        return $values ? $values[$key] : $default;
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        if (!isset($this->memcached)) {
            return false;
        }

        return $this->memcached->set($key, $value, $this->fixTtl($ttl));
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        if (!isset($this->memcached)) {
            return false;
        }

        return $this->memcached->delete($key);
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

        if (isset($this->memcached)) {
            $fetched = $this->memcached->getMulti($keys) ?: [];
        } else {
            $fetched = [];
        }

        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $fetched[$key] ?? $default;
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
        if (!isset($this->memcached)) {
            return false;
        }

        return $this->memcached->setMulti($values, $this->fixTtl($ttl));
    }

    /**
     * @inheritDoc
     *
     * @throws CacherException
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $keys = $this->checkKeys($keys);
        if (!isset($this->memcached)) {
            return false;
        }

        $this->memcached->deleteMulti($keys);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        if (!isset($this->memcached)) {
            return false;
        }

        return (bool) $this->memcached->getMulti([$key]);
    }
}
