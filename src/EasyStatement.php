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
     * @var EasyStatement|null
     */
    private $parent;
    /**
     * @return int
     */
    public function count()
    {
        return \count($this->parts);
    }
    /**
     * Open a new statement.
     *
     * @return self
     */
    public static function open()
    {
        return new static();
    }
    /**
     * Alias for andWith().
     *
     * @param string $condition
     * @param mixed ...$values
     *
     * @return self
     * @throws \TypeError
     */
    public function with(/*$condition, ...$values*/)
    {
        return \call_user_func_array(
            [$this, 'andWith'],
            \func_get_args()
        );
    }
    /**
     * Add a condition that will be applied with a logical "AND".
     *
     * @param string|self $condition
     * @param mixed ...$values
     *
     * @return self
     * @throws \TypeError
     * @psalm-suppress RedundantConditionGivenDocblockType
     */
    public function andWith(/*$condition, ...$values*/)
    {
        $arguments = func_get_args();
        $condition = \array_shift($arguments);

        if ($condition instanceof EasyStatement) {
            $condition = '(' . $condition . ')';
        }
        if (!\is_string($condition)) {
            throw new \TypeError('An EasyStatement or string is expected for argument 1');
        }
        \array_unshift($arguments, $condition);
        return \call_user_func_array(
            [$this, 'andWithString'],
            $arguments
        );
    }
    /**
     * Add a condition that will be applied with a logical "AND".
     *
     * @param string $condition
     * @param mixed ...$values
     *
     * @return self
     */
    public function andWithString(/*$condition, ...$values*/)
    {
        $arguments = func_get_args();
        $condition = \array_shift($arguments);
        $values = \array_values($arguments);
        $this->parts[] = ['type' => 'AND', 'condition' => $condition, 'values' => $values];
        return $this;
    }
    /**
     * Add a condition that will be applied with a logical "OR".
     *
     * @param string|self $condition
     * @param mixed ...$values
     *
     * @return self
     * @throws \TypeError
     * @psalm-suppress RedundantConditionGivenDocblockType
     */
    public function orWith(/*$condition, ...$values*/)
    {
        $arguments = func_get_args();
        $condition = \array_shift($arguments);

        if ($condition instanceof EasyStatement) {
            $condition = '(' . $condition . ')';
        }
        if (!\is_string($condition)) {
            throw new \TypeError('An EasyStatement or string is expected for argument 1');
        }
        \array_unshift($arguments, $condition);
        return \call_user_func_array(
            [$this, 'orWithString'],
            $arguments
        );
    }
    /**
     * Add a condition that will be applied with a logical "OR".
     *
     * @param string $condition
     * @param mixed ...$values
     *
     * @return self
     */
    public function orWithString(/*$condition, ...$values*/)
    {
        $arguments = func_get_args();
        $condition = \array_shift($arguments);
        $values = \array_values($arguments);

        $this->parts[] = ['type' => 'OR', 'condition' => $condition, 'values' => $values];
        return $this;
    }
    /**
     * Alias for andIn().
     *
     * @param string $condition
     * @param array $values
     *
     * @return self
     * @throws \TypeError
     */
    public function in($condition, array $values)
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
     * @throws \TypeError
     */
    public function andIn(/*$condition, array $values*/)
    {
        $arguments = \func_get_args();
        $condition = \array_shift($arguments);
        $values = \array_shift($arguments);

        $unpacked = $this->unpackCondition($condition, \count($values));
        \array_unshift($values, $unpacked);

        return \call_user_func_array(
            [$this, 'andWith'],
            $values
        );
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
     * @throws \TypeError
     */
    public function orIn($condition, array $values)
    {
        $arguments = \func_get_args();
        $condition = \array_shift($arguments);
        $values = \array_shift($arguments);

        $unpacked = $this->unpackCondition($condition, \count($values));
        \array_unshift($values, $unpacked);

        return \call_user_func_array(
            [$this, 'orWith'],
            $values
        );
    }
    /**
     * Alias for andGroup().
     *
     * @return self
     */
    public function group()
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
    public function andGroup()
    {
        $group = new self($this);
        $this->parts[] = ['type' => 'AND', 'condition' => $group];
        return $group;
    }
    /**
     * Start a new grouping that will be applied with a logical "OR".
     *
     * Exit the group with endGroup().
     *
     * @return self
     */
    public function orGroup()
    {
        $group = new self($this);
        $this->parts[] = ['type' => 'OR', 'condition' => $group];
        return $group;
    }
    /**
     * Alias for endGroup().
     *
     * @return self
     */
    public function end()
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
        if (empty($this->parts)) {
            return '1';
        }
        return (string) \array_reduce($this->parts, function ($sql, array $part) {
            /** @var string|self $condition */
            $condition = $part['condition'];
            if ($this->isGroup($condition)) {
                // (...)
                if (\is_string($condition)) {
                    $statement = '(' . $condition . ')';
                } else {
                    $statement = '(' . $condition->sql() . ')';
                }
            } else {
                // foo = ?
                $statement = $condition;
            }
            /** @var string $statement */
            $statement = (string) $statement;
            $part['type'] = (string) $part['type'];
            if ($sql) {
                switch ($part['type']) {
                    case 'AND':
                    case 'OR':
                        $statement = $part['type'] . ' ' . $statement;
                        break;
                    default:
                        throw new RuntimeException(\sprintf('Invalid joiner %s', $part['type']));
                }
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
        return (array) \array_reduce($this->parts, function (array $values, array $part) {
            if ($this->isGroup($part['condition'])) {
                /** @var EasyStatement $condition */
                $condition = $part['condition'];
                return \array_merge($values, $condition->values());
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
    protected function isGroup($condition)
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
     * @param int $count
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