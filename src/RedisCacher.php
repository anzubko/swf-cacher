<?php
declare(strict_types=1);

namespace SWF;

use DateInterval;
use Redis;
use RedisException;
use SWF\Exception\CacherException;
use function count;

class RedisCacher extends AbstractCacher
{
    public readonly Redis $instance;

    /**
     * @param string|null $ns Namespace prefix.
     * @param int|null $ttl Default TTL.
     * @param mixed[] $connect Server to connect.
     * @param mixed[] $options Redis options.
     *
     * @throws CacherException
     */
    public function __construct(?string $ns = null, ?int $ttl = null, array $connect = [], array $options = [])
    {
        $this->ttl = $ttl ?? $this->ttl;

        if (count($connect) === 0) {
            $connect = ['127.0.0.1', 6379, 2.5];
        }

        $options[Redis::OPT_PREFIX] = $ns ?? md5(__FILE__);
        $options[Redis::OPT_SERIALIZER] ??= Redis::SERIALIZER_PHP;

        try {
            $this->instance = new Redis();
            $this->instance->connect(...$connect);

            foreach ($options as $key => $value) {
                $this->instance->setOption($key, $value);
            }
        } catch (RedisException $e) {
            throw new CacherException($e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        try {
            [$value, $exists] = $this->instance->multi()->get($key)->exists($key)->exec();
        } catch (RedisException) {
            return $default;
        }

        return $exists ? $value : $default;
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $ttl = $this->fixTtl($ttl);

        try {
            if ($ttl > 0) {
                return $this->instance->set($key, $value, $ttl);
            } else {
                return $this->instance->set($key, $value);
            }
        } catch (RedisException) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        try {
            return (bool) $this->instance->del($key);
        } catch (RedisException) {
            return false;
        }
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

        $fetched = [];
        try {
            $this->instance->multi()->mGet($keys);

            foreach ($keys as $key) {
                $this->instance->exists($key);
            }

            $result = $this->instance->exec();

            foreach ($result[0] as $i => $value) {
                if ($result[$i + 1]) {
                    $fetched[$keys[$i]] = $value;
                }
            }
        } catch (RedisException) {
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
        $ttl = $this->fixTtl($ttl);

        try {
            $success = true;
            foreach ($this->checkValues($values) as $key => $value) {
                if ($ttl > 0) {
                    $success = $this->instance->set($key, $value, $ttl) ? $success : false;
                } else {
                    $success = $this->instance->set($key, $value) ? $success : false;
                }
            }

            return $success;
        } catch (RedisException) {
            return false;
        }
    }

    /**
     * @inheritDoc
     *
     * @throws CacherException
     */
    public function deleteMultiple(iterable $keys): bool
    {
        try {
            $this->instance->del($this->checkKeys($keys));
        } catch (RedisException) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        try {
            return (bool) $this->instance->exists($key);
        } catch (RedisException) {
            return false;
        }
    }
}
