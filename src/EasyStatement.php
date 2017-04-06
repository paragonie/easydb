<?php

namespace ParagonIE\EasyDB;

use RuntimeException;

/**
 * Class EasyStatement
 * @package ParagonIE\EasyDB
 */
class EasyStatement
{
    /**
     * @var array
     */
    private $parts = [];

    /**
     * @var EasyStatement
     */
    private $parent;

    /**
     * Open a new statement.
     *
     * @return self
     */
    public static function open(): self
    {
        return new static();
    }

    /**
     * Alias for andWith().
     *
     * @param string $condition
     * @param mixed $values, ...
     *
     * @return self
     */
    public function with(string $condition, ...$values): self
    {
        return $this->andWith($condition, ...$values);
    }

    /**
     * Add a condition that will be applied with a logical "AND".
     *
     * @param string $condition
     * @param mixed ...$values
     *
     * @return self
     */
    public function andWith(string $condition, ...$values): self
    {
        $this->parts[] = [
            'type' => 'AND',
            'condition' => $condition,
            'values' => $values,
        ];

        return $this;
    }

    /**
     * Add a condition that will be applied with a logical "OR".
     *
     * @param string $condition
     * @param mixed ...$values
     *
     * @return self
     */
    public function orWith(string $condition, ...$values): self
    {
        $this->parts[] = [
            'type' => 'OR',
            'condition' => $condition,
            'values' => $values,
        ];

        return $this;
    }

    /**
     * Alias for andIn().
     *
     * @param string $condition
     * @param array $values
     *
     * @return self
     */
    public function in(string $condition, array $values): self
    {
        return $this->andIn($condition, $values);
    }

    /**
     * Add an IN condition that will be applied with a logical "AND".
     *
     * Instead of using ? to denote the placeholder, ?* must be used!
     *
     * @param string $condition
     * @param array $values
     *
     * @return self
     */
    public function andIn(string $condition, array $values): self
    {
        return $this->andWith($this->unpackCondition($condition, \count($values)), ...$values);
    }

    /**
     * Add an IN condition that will be applied with a logical "OR".
     *
     * Instead of using "?" to denote the placeholder, "?*" must be used!
     *
     * @param string $condition
     * @param array $values
     *
     * @return self
     */
    public function orIn(string $condition, array $values): self
    {
        return $this->orWith($this->unpackCondition($condition, \count($values)), ...$values);
    }

    /**
     * Alias for andGroup().
     *
     * @return self
     */
    public function group(): self
    {
        return $this->andGroup();
    }

    /**
     * Start a new grouping that will be applied with a logical "AND".
     *
     * Exit the group with endGroup().
     *
     * @return self
     */
    public function andGroup(): self
    {
        $group = new self($this);

        $this->parts[] = [
            'type' => 'AND',
            'condition' => $group,
        ];

        return $group;
    }

    /**
     * Start a new grouping that will be applied with a logical "OR".
     *
     * Exit the group with endGroup().
     *
     * @return self
     */
    public function orGroup(): self
    {
        $group = new self($this);

        $this->parts[] = [
            'type' => 'OR',
            'condition' => $group,
        ];

        return $group;
    }

    /**
     * Alias for endGroup().
     *
     * @return self
     */
    public function end(): self
    {
        return $this->endGroup();
    }

    /**
     * Exit the current grouping and return the parent statement.
     *
     * @return self
     *
     * @throws RuntimeException
     *  If the current statement has no parent context.
     */
    public function endGroup(): self
    {
        if (empty($this->parent)) {
            throw new RuntimeException('Already at the top of the statement');
        }

        return $this->parent;
    }

    /**
     * Compile the current statement into PDO-ready SQL.
     *
     * If an EasyDB instance is provided, any fully qualified identifier
     * (`table.col`) will be escaped. Note that this functionality does not
     * work when magic casting the statement to string!
     *
     * @param EasyDB $db
     *
     * @return string
     */
    public function sql(EasyDB $db = null): string
    {
        return \array_reduce(
            $this->parts,
            function (string $sql, array $part) use ($db): string {
                if ($this->isGroup($part['condition'])) {
                    // (...)
                    $statement = '(' . $part['condition']->sql($db) . ')';
                } else {
                    // foo = ?
                    $statement = $part['condition'];
                    if ($db) {
                        $statement = $this->escapeIdentifiersInCondition($statement, $db);
                    }
                }

                if ($sql) {
                    switch ($part['type']) {
                        case 'AND':
                        case 'OR':
                            $statement = $part['type'] . ' ' . $statement;
                            break;
                        default:
                            throw new RuntimeException(
                                \sprintf('Invalid joiner %s', $part['type'])
                            );
                    }
                }

                return \trim($sql . ' ' . $statement);
            },
            ''
        );
    }

    /**
     * Get all of the parameters attached to this statement.
     *
     * @return array
     */
    public function values(): array
    {
        return \array_reduce(
            $this->parts,
            function (array $values, array $part): array {
                if ($this->isGroup($part['condition'])) {
                    return \array_merge(
                        $values,
                        $part['condition']->values()
                    );
                }
                return \array_merge($values, $part['values']);
            },
            []
        );
    }

    /**
     * Convert the statement to a string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->sql();
    }

    /**
     * Don't instantiate directly. Instead, use open() (static method).
     *
     * EasyStatement constructor.
     * @param EasyStatement|null $parent
     */
    protected function __construct(EasyStatement $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * Check if a condition is a sub-group.
     *
     * @param mixed $condition
     *
     * @return bool
     */
    protected function isGroup($condition): bool
    {
        if (!\is_object($condition)) {
            return false;
        }

        return $condition instanceof EasyStatement;
    }

    /**
     * Replace a grouped placeholder with a list of placeholders.
     *
     * Given a count of 3, the placeholder ?* will become ?, ?, ?
     *
     * @param string $condition
     * @param integer $count
     *
     * @return string
     */
    private function unpackCondition(string $condition, int $count): string
    {
        // Replace a grouped placeholder with an matching count of placeholders.
        $params = '?' . \str_repeat(', ?', $count - 1);
        return \str_replace('?*', $params, $condition);
    }

    /**
     * Escape fully qualified identifiers.
     *
     * @param string $condition
     * @param EasyDB $db
     *
     * @return string
     */
    private function escapeIdentifiersInCondition(string $condition, EasyDB $db): string
    {
        if (\preg_match_all('/([a-z_]+\.[a-z_]+)/i', $condition, $matches, \PREG_SET_ORDER) == false) {
            return $condition;
        }

        $replace = [];
        foreach ($matches as $match) {
            $replace[$match[0]] = $db->escapeIdentifier($match[1]);
        }

        return \strtr($condition, $replace);
    }
}
