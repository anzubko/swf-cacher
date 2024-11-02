<?php
declare(strict_types=1);

namespace SWF;

use DateInterval;
use DateTime;
use SWF\Exception\CacherException;
use SWF\Interface\CacherInterface;
use function is_int;
use function is_string;

abstract class AbstractCacher implements CacherInterface
{
    protected int $ttl = 0;

    /**
     * @param iterable<string> $keys
     *
     * @return string[]
     *
     * @throws CacherException
     */
    protected function checkKeys(iterable $keys): array
    {
        foreach ($keys as $key) {
            if (!is_string($key) && !is_int($key)) {
                throw new CacherException('Keys must be strings');
            }
        }

        return iterator_to_array($keys);
    }

    /**
     * @param iterable<mixed> $values
     *
     * @return mixed[]
     *
     * @throws CacherException
     */
    protected function checkValues(iterable $values): array
    {
        foreach ($values as $key => $value) {
            if (!is_string($key) && !is_int($key)) {
                throw new CacherException('Keys must be strings');
            }
        }

        return iterator_to_array($values);
    }

    protected function fixTtl(null|int|DateInterval $ttl): int
    {
        if ($ttl !== null) {
            if ($ttl instanceof DateInterval) {
                $ttl = (new DateTime())->setTimestamp(0)->add($ttl)->getTimestamp();
            } else {
                $ttl = (int) $ttl;
            }

            if ($ttl < 0) {
                $ttl = 0;
            }
        } else {
            $ttl = $this->ttl;
        }

        return $ttl;
    }
}
