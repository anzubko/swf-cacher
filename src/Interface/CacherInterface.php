<?php declare(strict_types=1);

namespace SWF\Interface;

use DateInterval;
use Psr\SimpleCache\CacheInterface;
use SWF\Exception\CacherException;

interface CacherInterface extends CacheInterface
{
    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool;

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool;

    /**
     * Not implemented!
     */
    public function clear(): bool;

    /**
     * @inheritDoc
     *
     * @param iterable<string> $keys
     *
     * @return mixed[]
     *
     * @throws CacherException
     */
    public function getMultiple(iterable $keys, mixed $default = null): array;

    /**
     * @inheritDoc
     *
     * @param iterable<mixed> $values
     *
     * @throws CacherException
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool;

    /**
     * @inheritDoc
     *
     * @param iterable<string> $keys
     *
     * @throws CacherException
     */
    public function deleteMultiple(iterable $keys): bool;

    /**
     * @inheritDoc
     */
    public function has(string $key): bool;
}
