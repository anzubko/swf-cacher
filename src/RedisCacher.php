<?php declare(strict_types=1);

namespace SWF;

use DateInterval;
use Redis;
use RedisException;
use SWF\Exception\CacherException;

class RedisCacher extends AbstractCacher
{
    protected Redis $redis;

    /**
     * @param string|null $ns Namespace prefix.
     * @param int|null $ttl Default TTL.
     * @param mixed[]|null $connect Server to connect.
     * @param mixed[]|null $options Redis options.
     *
     * @throws CacherException
     */
    public function __construct(?string $ns = null, ?int $ttl = 0, ?array $connect = [], ?array $options = [])
    {
        if (!extension_loaded('redis')) {
            return;
        }

        $this->ttl = $ttl ?? $this->ttl;

        if (empty($connect)) {
            $connect = ['127.0.0.1', 6379, 2.5];
        }

        $options[Redis::OPT_PREFIX] = $ns ?? md5(__FILE__);
        $options[Redis::OPT_SERIALIZER] ??= Redis::SERIALIZER_PHP;

        try {
            $this->redis = new Redis();
            $this->redis->connect(...$connect);

            foreach ($options as $key => $value) {
                $this->redis->setOption($key, $value);
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
        if (!isset($this->redis)) {
            return $default;
        }

        try {
            [$value, $exists] = $this->redis->multi()->get($key)->exists($key)->exec();
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
        if (!isset($this->redis)) {
            return false;
        }

        $ttl = $this->fixTtl($ttl);

        try {
            if ($ttl > 0) {
                return $this->redis->set($key, $value, $ttl);
            } else {
                return $this->redis->set($key, $value);
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
        if (!isset($this->redis)) {
            return false;
        }

        try {
            return (bool) $this->redis->del($key);
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
        if (isset($this->redis)) {
            try {
                $this->redis->multi()->mGet($keys);

                foreach ($keys as $key) {
                    $this->redis->exists($key);
                }

                $result = $this->redis->exec();

                foreach ($result[0] as $i => $value) {
                    if ($result[$i + 1]) {
                        $fetched[$keys[$i]] = $value;
                    }
                }
            } catch (RedisException) {
            }
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
        if (!isset($this->redis)) {
            return false;
        }

        $ttl = $this->fixTtl($ttl);

        try {
            $success = true;
            if ($ttl > 0) {
                foreach ($values as $key => $value) {
                    $success = $this->redis->set($key, $value, $ttl) ? $success : false;
                }
            } else {
                foreach ($values as $key => $value) {
                    $success = $this->redis->set($key, $value) ? $success : false;
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
        $keys = $this->checkKeys($keys);
        if (!isset($this->redis)) {
            return false;
        }

        try {
            $this->redis->del($keys);
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
        if (!isset($this->redis)) {
            return false;
        }

        try {
            return (bool) $this->redis->exists($key);
        } catch (RedisException) {
            return false;
        }
    }
}
