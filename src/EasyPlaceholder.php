<?php

namespace ParagonIE\EasyDB;

use ParagonIE\EasyDB\Exception\MustBeNonEmpty;
use ParagonIE\EasyDB\Exception\EasyDBException;
use function
    array_merge,
    array_reduce,
    is_array,
    strpos,
    substr,
    str_repeat;

/**
 * Class EasyPlaceholder
 *
 * @package ParagonIE\EasyDB
 *
 * @example
 */
class EasyPlaceholder
{
    protected string $mask;
    /** @var array<array-key, scalar> $values */
    protected array $values = [];

    /**
     * Use custom mask for set value in INSERT or UPDATE
     *
     * @param string $mask
     * @param scalar|array|null ...$values
     *
     * @throws MustBeNonEmpty
     *
     * @psalm-taint-source input $mask
     */
    public function __construct(string $mask, ...$values)
    {
        $values = array_reduce($values, function ($values, $value) use (&$mask) {
            if (!is_array($value)) {
                $values []= $value;
                return $values;
            }
            $start_pos = strpos($mask, '?*');
            if ($start_pos === false) {
                throw new EasyDBException("Mask don't have \"?*\"");
            }
            if (count($value) < 1) {
                throw new MustBeNonEmpty();
            }
            $mask = substr($mask, 0, $start_pos)
                . "?"
                . str_repeat(', ?', \count($value) - 1)
                . substr($mask, $start_pos + 2);
            return array_merge($values, $value);
        }, []);
        $this->mask = $mask;
        $this->values = $values;
    }

    /**
     * @return string
     */
    public function mask(): string
    {
        return $this->mask;
    }

    /**
     * @return array
     */
    public function values(): array
    {
        return $this->values;
    }
}
