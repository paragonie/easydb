<?php

namespace ParagonIE\EasyDB;

use RuntimeException;

class EasyStatement
{
    /**
     * Open a new statement.
     *
     * @return static
     */
    public static function open(): EasyStatement
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
    public function with(string $condition, ...$values): EasyStatement
    {
        return $this->andWith($condition, ...$values);
    }

    /**
     * Add a condition that will be applied with a logical "AND".
     *
     * @param string $condition
     * @param ... $values
     *
     * @return self
     */
    public function andWith(string $condition, ...$values): EasyStatement
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
     * @param ... $values
     *
     * @return self
     */
    public function orWith(string $condition, ...$values): EasyStatement
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
    public function in(string $condition, array $values): EasyStatement
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
    public function andIn(string $condition, array $values): EasyStatement
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
    public function orIn(string $condition, array $values): EasyStatement
    {
        return $this->orWith($this->unpackCondition($condition, \count($values)), ...$values);
    }

    /**
     * Alias for andGroup().
     *
     * @return static
     */
    public function group(): EasyStatement
    {
        return $this->andGroup();
    }

    /**
     * Start a new grouping that will be applied with a logical "AND".
     *
     * Exit the group with endGroup().
     *
     * @return static
     */
    public function andGroup(): EasyStatement
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
     * @return static
     */
    public function orGroup(): EasyStatement
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
     * @return static
     */
    public function end(): EasyStatement
    {
        return $this->endGroup();
    }

    /**
     * Exit the current grouping and return the parent statement.
     *
     * @return static
     *
     * @throws RuntimeException
     *  If the current statement has no parent context.
     */
    public function endGroup(): EasyStatement
    {
        if (empty($this->parent)) {
            throw new RuntimeException('Already at the top of the statement');
        }

        return $this->parent;
    }

    /**
     * Compile the current statement into PDO-ready SQL.
     *
     * @return string
     */
    public function sql(): string
    {
        return \array_reduce($this->parts, function (string $sql, array $part) {
            if ($this->isGroup($part['condition'])) {
                // (...)
                $statement = '(' . $part['condition']->sql() . ')';
            } else {
                // foo = ?
                $statement = $part['condition'];
            }

            if ($sql) {
                // AND|OR ...
                $statement = $part['type'] . ' ' . $statement;
            }

            return \trim($sql . ' ' . $statement);
        }, '');
    }

    /**
     * Get all of the parameters attached to this statement.
     *
     * @return array
     */
    public function values(): array
    {
        return array_reduce($this->parts, function (array $values, array $part) {
            if ($this->isGroup($part['condition'])) {
                return \array_merge($values, $part['condition']->values());
            }

            return \array_merge($values, $part['values']);
        }, []);
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
     * @var array
     */
    private $parts = [];

    /**
     * @var EasyStatement
     */
    private $parent;

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
        if (false === \is_object($condition)) {
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
}
