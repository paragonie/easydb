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
    public static function open()
    {
        return new static();
    }

    /**
     * Add a condition that will be applied with a logical "AND".
     *
     * @param string $condition
     * @param ... $values
     *
     * @return self
     */
    public function andWith($condition, ...$values)
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
    public function orWith($condition, ...$values)
    {
        $this->parts[] = [
            'type' => 'OR',
            'condition' => $condition,
            'values' => $values,
        ];

        return $this;
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
    public function andIn($condition, array $values)
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
    public function orIn($condition, array $values)
    {
        return $this->orWith($this->unpackCondition($condition, \count($values)), ...$values);
    }

    /**
     * Start a new grouping that will be applied with a logical "AND".
     *
     * Exit the group with endGroup().
     *
     * @return static
     */
    public function andGroup()
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
    public function orGroup()
    {
        $group = new self($this);

        $this->parts[] = [
            'type' => 'OR',
            'condition' => $group,
        ];

        return $group;
    }

    /**
     * Exit the current grouping and return the parent statement.
     *
     * @return static
     *
     * @throws RuntimeException
     *  If the current statement has no parent context.
     */
    public function endGroup()
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
    public function sql()
    {
        return \array_reduce($this->parts, function ($sql, $part) {
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
    public function values()
    {
        return array_reduce($this->parts, function ($values, $part) {
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
    public function __toString()
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
    protected function isGroup($condition)
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
    private function unpackCondition($condition, $count)
    {
        // Replace a grouped placeholder with an matching count of placeholders.
        $params = '?' . \str_repeat(', ?', $count - 1);
        return \str_replace('?*', $params, $condition);
    }
}
