<?php declare(strict_types=1);

namespace SWF;

use DateInterval;
use Memcached;
use SWF\Exception\CacherException;
use function count;

class MemCacher extends AbstractCacher
{
    public readonly Memcached $instance;

    /**
     * @param string|null $ns Namespace prefix.
     * @param int|null $ttl Default TTL.
     * @param mixed[][] $servers Servers to connect.
     * @param mixed[] $options Memcached options.
     */
    public function __construct(?string $ns = null, ?int $ttl = null, array $servers = [], array $options = [])
    {
        $this->ttl = $ttl ?? $this->ttl;

        if (count($servers) === 0) {
            $servers = [['127.0.0.1', 11211]];
        }

        $options[Memcached::OPT_PREFIX_KEY] = $ns ?? md5(__FILE__);

        $this->instance = new Memcached();
        $this->instance->addServers($servers);
        $this->instance->setOptions($options);
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $values = $this->instance->getMulti([$key]);

        return $values ? $values[$key] : $default;
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        return $this->instance->set($key, $value, $this->fixTtl($ttl));
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        return $this->instance->delete($key);
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

        $fetched = $this->instance->getMulti($keys);
        if (false === $fetched) {
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
        return $this->instance->setMulti($this->checkValues($values), $this->fixTtl($ttl));
    }

    /**
     * @inheritDoc
     *
     * @throws CacherException
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $this->instance->deleteMulti($this->checkKeys($keys));

        return true;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return (bool) $this->instance->getMulti([$key]);
    }
}
