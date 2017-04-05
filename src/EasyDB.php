<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB;

use \ParagonIE\EasyDB\Exception as Issues;

/**
 * Class EasyDB
 * @package ParagonIE\EasyDB
 */
class EasyDB
{
    /**
     * @var string
     */
    protected $dbEngine = null;

    /**
     * @var \PDO
     */
    protected $pdo = null;

    /**
     * @var bool
     */
    protected $allowSeparators = false;

    /**
     * Dependency-Injectable constructor
     *
     * @param \PDO $pdo
     * @param string $dbEngine
     */
    public function __construct(\PDO $pdo, string $dbEngine = '')
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(
            \PDO::ATTR_EMULATE_PREPARES,
            false
        );
        $this->pdo->setAttribute(
            \PDO::ATTR_ERRMODE,
            \PDO::ERRMODE_EXCEPTION
        );

        if (empty($dbEngine)) {
            $dbEngine = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        }

        $this->dbEngine = $dbEngine;
    }

    /**
     * Variadic version of $this->column()
     *
     * @param string $statement SQL query without user data
     * @param int $offset       How many columns from the left are we grabbing
     *                          from each row?
     * @param mixed ...$params  Parameters
     * @return mixed
     */
    public function col(string $statement, int $offset = 0, ...$params)
    {
        return $this->column($statement, $params, $offset);
    }

    /**
     * Fetch a column
     *
     * @param string $statement SQL query without user data
     * @param array $params     Parameters
     * @param int $offset       How many columns from the left are we grabbing
     *                          from each row?
     * @return mixed
     */
    public function column(string $statement, array $params = [], int $offset = 0)
    {
        $stmt = $this->pdo->prepare($statement);
        if (!$this->is1DArray($params)) {
            throw new \InvalidArgumentException(
                'Only one-dimensional arrays are allowed.'
            );
        }
        $stmt->execute($params);
        return $stmt->fetchAll(
            \PDO::FETCH_COLUMN,
            $offset
        );
    }

    /**
     * Variadic version of $this->single()
     *
     * @param string $statement SQL query without user data
     * @param mixed ...$params Parameters
     * @return mixed
     */
    public function cell(string $statement, ...$params)
    {
        return $this->single($statement, $params);
    }

    /**
     * Delete rows in a database table.
     *
     * @param string $table - table name
     * @param array $conditions - WHERE clause
     * @return int
     * @throws \InvalidArgumentException
     */
    public function delete(string $table, array $conditions): int
    {
        if (empty($table)) {
            throw new \InvalidArgumentException(
                'Table name must be a non-empty string.'
            );
        }
        if (empty($conditions)) {
            // Don't allow foot-bullets
            return 0;
        }
        if (!$this->is1DArray($conditions)) {
            throw new \InvalidArgumentException(
                'Only one-dimensional arrays are allowed.'
            );
        }
        $queryString = 'DELETE FROM ' . $this->escapeIdentifier($table) . ' WHERE ';

        // Simple array for joining the strings together
        $params = [];
        $arr = [];
        foreach ($conditions as $i => $v) {
            $i = $this->escapeIdentifier($i);
            if ($v === null) {
                $arr [] = " {$i} IS NULL ";
            } elseif ($v === true) {
                $arr [] = " {$i} = TRUE ";
            } elseif ($v === false) {
                $arr [] = " {$i} = FALSE ";
            } else {
                $arr []= " {$i} = ? ";
                $params[] = $v;
            }
        }
        $queryString .= \implode(' AND ', $arr);

        return (int) $this->safeQuery(
            $queryString,
            $params,
            \PDO::FETCH_BOTH,
            true
        );
    }

    /**
     * Make sure only valid characters make it in column/table names
     *
     * @ref https://stackoverflow.com/questions/10573922/what-does-the-sql-standard-say-about-usage-of-backtick
     *
     * @param string $string - table or column name
     * @param boolean $quote - certain SQLs escape column names (i.e. mysql with `backticks`)
     * @return string
     */
    public function escapeIdentifier(string $string, $quote = true): string
    {
        if (empty($string)) {
            throw new Issues\InvalidIdentifier(
                'Invalid identifier: Must be a non-empty string.'
            );
        }
        if ($this->allowSeparators) {
            $str = \preg_replace('/[^\.0-9a-zA-Z_]/', '', $string);
            if (\strpos($str, '.') !== false) {
                $pieces = \explode('.', $str);
                foreach ($pieces as $i => $p) {
                    $pieces[$i] = $this->escapeIdentifier($p, $quote);
                }
                return \implode('.', $pieces);
            }
        } else {
            $str = \preg_replace('/[^0-9a-zA-Z_]/', '', $string);
            if ($str !== \trim($string)) {
                if ($str === \str_replace('.', '', $string)) {
                    throw new Issues\InvalidIdentifier(
                        'Separators (.) are not permitted.'
                    );
                }
                throw new Issues\InvalidIdentifier(
                    'Invalid identifier: Invalid characters supplied.'
                );
            }
        }

        // The first character cannot be [0-9]:
        if (\preg_match('/^[0-9]/', $str)) {
            throw new Issues\InvalidIdentifier(
                'Invalid identifier: Must begin with a letter or underscore.'
            );
        }

        if ($quote) {
            switch ($this->dbEngine) {
                case 'mssql':
                    return '[' . $str . ']';
                case 'mysql':
                    return '`' . $str . '`';
                default:
                    return '"' . $str . '"';
            }
        }
        return $str;
    }

    /**
     * Create a parenthetical statement e.g. for NOT IN queries.
     *
     * Input: ([1, 2, 3, 5], int)
     * Output: "(1,2,3,5)"
     *
     * @param array $values
     * @param string $type
     * @return string
     * @throws \InvalidArgumentException
     */
    public function escapeValueSet(array $values, string $type = 'string'): string
    {
        if (empty($values)) {
            // Default value: a sub-query that will return an empty set
            return '(SELECT 1 WHERE FALSE)';
        }
        // No arrays of arrays, please
        if (!$this->is1DArray($values)) {
            throw new \InvalidArgumentException(
                'Only one-dimensional arrays are allowed.'
            );
        }
        // Build our array
        $join = [];
        foreach ($values as $k => $v) {
            switch ($type) {
                case 'int':
                    if (!\is_int($v)) {
                        throw new \InvalidArgumentException(
                            'Expected a integer at index ' .
                                $k .
                            ' of argument 1 passed to ' .
                            static::class .
                            '::' .
                            __METHOD__ .
                            '(), received ' .
                            (
                                (
                                    \is_scalar($v) || \is_array($v)
                                )
                                    ? \gettype($v)
                                    : (
                                        \is_object($v)
                                            ? ('an instance of ' . \get_class($v))
                                            : \var_export($v, true)
                                    )
                            )
                        );
                    }
                    $join[] = (int) $v + 0;
                    break;
                case 'float':
                case 'decimal':
                case 'number':
                case 'numeric':
                    if (!\is_numeric($v)) {
                        throw new \InvalidArgumentException(
                            'Expected a number at index ' .
                                $k .
                            ' of argument 1 passed to ' .
                            static::class .
                            '::' .
                            __METHOD__ .
                            '(), received ' .
                            (
                                (
                                    \is_scalar($v) || \is_array($v)
                                )
                                    ? \gettype($v)
                                    : (
                                        \is_object($v)
                                            ? ('an instance of ' . \get_class($v))
                                            : \var_export($v, true)
                                    )
                            )
                        );
                    }
                    $join[] = (float) $v + 0.0;
                    break;
                case 'string':
                    if (\is_numeric($v)) {
                        $v = (string) $v;
                    }
                    if (!\is_string($v)) {
                        throw new \InvalidArgumentException(
                            'Expected a string at index ' .
                                $k .
                            ' of argument 1 passed to ' .
                            static::class .
                            '::' .
                            __METHOD__ .
                            '(), received ' .
                            (
                                (
                                    \is_scalar($v) || \is_array($v)
                                )
                                    ? \gettype($v)
                                    : (
                                        \is_object($v)
                                            ? ('an instance of ' . \get_class($v))
                                            : \var_export($v, true)
                                    )
                            )
                        );
                    }
                    $join[] = $this->pdo->quote($v, \PDO::PARAM_STR);
                    break;
                default:
                    break 2;
            }
        }
        if (empty($join)) {
            return '(SELECT 1 WHERE FALSE)';
        }
        return '(' . \implode(', ', $join) . ')';
    }

    /**
     * Escape a value that will be used as a LIKE condition.
     *
     * Input: ("string_not%escaped")
     * Output: "string\_not\%escaped"
     *
     * WARNING: This function always escapes wildcards using backslash!
     *
     * @param string $value
     * @return string
     */
    public function escapeLikeValue(string $value): string
    {
        // Backslash is used to escape wildcards.
        $value = str_replace('\\', '\\\\', $value);
        // Standard wildcards are underscore and percent sign.
        $value = str_replace('%', '\\%', $value);
        $value = str_replace('_', '\\_', $value);

        if ($this->dbEngine === 'mssql') {
            // MSSQL also includes character ranges.
            $value = str_replace('[', '\\[', $value);
            $value = str_replace(']', '\\]', $value);
        }

        return $value;
    }

    /**
     * Use with SELECT COUNT queries to determine if a record exists.
     *
     * @param string $statement
     * @param array ...$params
     * @return bool
     */
    public function exists(string $statement, ...$params): bool
    {
        $result = $this->single($statement, $params);
        return !empty($result);
    }

    /**
     * Get the first column of each row
     *
     * @param $statement
     * @param array ...$params
     * @return mixed
     */
    public function first(string $statement, ...$params)
    {
        return $this->column($statement, $params, 0);
    }

    /**
     * Which database driver are we operating on?
     *
     * @return string
     */
    public function getDriver(): string
    {
        return $this->dbEngine;
    }

    /**
     * Return a copy of the PDO object (to prevent it from being modified
     * to disable safety/security features).
     *
     * @return \PDO
     */
    public function getPdo(): \PDO
    {
        return $this->pdo;
    }

    /**
     * Insert a new row to a table in a database.
     *
     * @param string $table - table name
     * @param array $map - associative array of which values should be assigned to each field
     * @return int
     * @throws \InvalidArgumentException
     */
    public function insert(string $table, array $map): int
    {
        if (!empty($map)) {
            if (!$this->is1DArray($map)) {
                throw new \InvalidArgumentException(
                    'Only one-dimensional arrays are allowed.'
                );
            }
        }

        $columns = \array_keys($map);
        $values = \array_values($map);

        $queryString = $this->buildInsertQuery($table, $columns);

        return (int) $this->safeQuery(
            $queryString,
            $values,
            \PDO::FETCH_BOTH,
            true
        );
    }

    /**
     * Insert a new record then get a particular field from the new row
     *
     * @param string $table
     * @param array $map
     * @param string $field
     * @return mixed
     * @throws \Exception
     */
    public function insertGet(string $table, array $map, string $field)
    {
        if ($this->insert($table, $map) < 1) {
            throw new \Exception('Insert failed');
        }
        $post = [];
        $params = [];
        foreach ($map as $i => $v) {
            // Escape the identifier to prevent stupidity
            $i = $this->escapeIdentifier($i);
            if ($v === null) {
                $post []= " {$i} IS NULL ";
            } elseif ($v === true) {
                $post []= " {$i} = TRUE ";
            } elseif ($v === false) {
                $post []= " {$i} = FALSE ";
            } else {
                // We use prepared statements for handling the users' data
                $post []= " {$i} = ? ";
                $params[] = $v;
            }
        }
        $conditions = \implode(' AND ', $post);
        // We want the latest value:
        switch ($this->dbEngine) {
            case 'mysql':
                $limiter = ' ORDER BY ' .
                    $this->escapeIdentifier($field) .
                    ' DESC LIMIT 0, 1 ';
                break;
            case 'pgsql':
                $limiter = ' ORDER BY ' .
                    $this->escapeIdentifier($field) .
                    ' DESC OFFSET 0 LIMIT 1 ';
                break;
            default:
                $limiter = '';
        }
        $query = 'SELECT ' .
                $this->escapeIdentifier($field) .
            ' FROM ' .
                $this->escapeIdentifier($table) .
            ' WHERE ' .
                $conditions .
                $limiter;
        return $this->single($query, $params);
    }

    /**
     * Insert many new rows to a table in a database. using the same prepared statement
     *
     * @param string $table - table name
     * @param array $maps - array of associative array specifying values should be assigned to each field
     * @return int
     * @throws \InvalidArgumentException
     * @throws Issues\QueryError
     */
    public function insertMany(string $table, array $maps): int
    {
        if (count($maps) < 1) {
            throw new \InvalidArgumentException(
                'Argument 2 passed to ' .
                static::class .
                '::' .
                __METHOD__ .
                '() must contain at least one field set!'
            );
        }
        $first = $maps[0];
        foreach ($maps as $map) {
            if (!$this->is1DArray($map)) {
                throw new \InvalidArgumentException(
                    'Every map in the second argument should have the same number of columns.'
                );
            }
        }

        $queryString = $this->buildInsertQuery($table, \array_keys($first));

        // Now let's run a query with the parameters
        $stmt = $this->pdo->prepare($queryString);
        $count = 0;
        foreach ($maps as $params) {
            $stmt->execute(\array_values($params));
            $count += $stmt->rowCount();
        }
        return $count;
    }

    /**
     * Get an query string for an INSERT statement.
     *
     * @param string $table
     * @param array $columns list of columns that will be inserted
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     *   If $columns is not a one-dimensional array.
     */
    public function buildInsertQuery(string $table, array $columns): string
    {
        if (!empty($columns)) {
            if (!$this->is1DArray($columns)) {
                throw new \InvalidArgumentException(
                    'Only one-dimensional arrays are allowed.'
                );
            }
        }

        $query = 'INSERT INTO %s (%s) VALUES (%s)';

        $columns = \array_map([$this, 'escapeIdentifier'], $columns);
        $placeholders = \array_fill(0, \count($columns), '?');

        return \sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->escapeIdentifier($table),
            \implode(', ', $columns),
            \implode(', ', $placeholders)
        );
    }

    /**
     * Variadic shorthand for $this->safeQuery()
     *
     * @param string $statement SQL query without user data
     * @param mixed ...$params Parameters
     * @return mixed
     */
    public function q(string $statement, ...$params)
    {
        return $this->safeQuery($statement, $params);
    }

    /**
     * Similar to $this->q() except it only returns a single row
     *
     * @param string $statement SQL query without user data
     * @param mixed ...$params Parameters
     * @return mixed
     */
    public function row(string $statement, ...$params)
    {
        $result = $this->safeQuery($statement, $params);
        if (\is_array($result)) {
            return \array_shift($result);
        }
        return [];
    }

    /**
     * Variadic shorthand for $this->safeQuery()
     *
     * @param string $statement SQL query without user data
     * @param mixed ...$params Parameters
     * @return mixed - If successful, a 2D array
     */
    public function run(string $statement, ...$params)
    {
        return $this->safeQuery($statement, $params);
    }

    /**
     * Perform a Parametrized Query
     *
     * @param string $statement          The query string (hopefully untainted
     *                                   by user input)
     * @param array $params              The parameters (used in prepared
     *                                   statements)
     * @param int $fetchStyle            PDO::FETCH_STYLE
     * @param bool $returnNumAffected    Return the number of rows affected?
     * @return array|int
     * @throws \InvalidArgumentException
     * @throws Issues\QueryError
     */
    public function safeQuery(
        string $statement,
        array $params = [],
        int $fetchStyle = \PDO::FETCH_ASSOC,
        bool $returnNumAffected = false
    ) {
        if (empty($params)) {
            $stmt = $this->pdo->query($statement);
            if ($returnNumAffected) {
                return $stmt->rowCount();
            }
            return $stmt->fetchAll($fetchStyle);
        }
        if (!$this->is1DArray($params)) {
            throw new \InvalidArgumentException(
                'Only one-dimensional arrays are allowed.'
            );
        }
        $stmt = $this->pdo->prepare($statement);
        $stmt->execute($params);
        if ($returnNumAffected) {
            return $stmt->rowCount();
        }
        return $stmt->fetchAll($fetchStyle);
    }

    /**
     * Fetch a single result -- useful for SELECT COUNT() queries
     *
     * @param string $statement
     * @param array $params
     * @return mixed
     * @throws \InvalidArgumentException
     * @throws Issues\QueryError
     */
    public function single(string $statement, array $params = [])
    {
        if (!$this->is1DArray($params)) {
            throw new \InvalidArgumentException(
                'Only one-dimensional arrays are allowed.'
            );
        }
        $stmt = $this->pdo->prepare($statement);
        $stmt->execute($params);
        return $stmt->fetchColumn(0);
    }

    /**
     * Update a row in a database table.
     *
     * @param string $table     Table name
     * @param array $changes    Associative array of which values should be
     *                            assigned to each field
     * @param array $conditions WHERE clause
     * @return int
     * @throws \InvalidArgumentException
     * @throws Issues\QueryError
     */
    public function update(string $table, array $changes, array $conditions): int
    {
        if (empty($changes) || empty($conditions)) {
            return 0;
        }
        if (!$this->is1DArray($changes) || !$this->is1DArray($conditions)) {
            throw new \InvalidArgumentException(
                'Only one-dimensional arrays are allowed.'
            );
        }
        $queryString = 'UPDATE ' . $this->escapeIdentifier($table) . ' SET ';
        $params = [];

        // The first set (pre WHERE)
        $pre = [];
        foreach ($changes as $i => $v) {
            $i = $this->escapeIdentifier($i);
            if ($v === null) {
                $pre []= " {$i} = NULL";
            } elseif ($v === true) {
                $pre []= " {$i} = TRUE";
            } elseif ($v === false) {
                $pre []= " {$i} = FALSE";
            } else {
                $pre []= " {$i} = ?";
                $params[] = $v;
            }
        }
        $queryString .= \implode(', ', $pre);
        $queryString .= " WHERE ";

        // The last set (post WHERE)
        $post = [];
        foreach ($conditions as $i => $v) {
            $i = $this->escapeIdentifier($i);
            if ($v === null) {
                $post []= " {$i} IS NULL";
            } elseif ($v === true) {
                $post []= " {$i} = TRUE";
            } elseif ($v === false) {
                $post []= " {$i} = FALSE";
            } else {
                $post []= " {$i} = ? ";
                $params[] = $v;
            }
        }
        $queryString .= \implode(' AND ', $post);

        return (int) $this->safeQuery(
            $queryString,
            $params,
            \PDO::FETCH_BOTH,
            true
        );
    }

    /**
     * @param bool $value
     * @return EasyDB
     */
    public function setAllowSeparators(bool $value): self
    {
        $this->allowSeparators = $value;
        return $this;
    }

    /**
     ***************************************************************************
     ***************************************************************************
     ****             PUNTER METHODS - see PDO class definition             ****
     ***************************************************************************
     ***************************************************************************
    **/

    /**
     * Initiates a transaction
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commits a transaction
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Fetch the SQLSTATE associated with the last operation on the database
     * handle
     *
     * @return mixed
     */
    public function errorCode()
    {
        return $this->pdo->errorCode();
    }
    /**
     * Fetch extended error information associated with the last operation on
     * the database handle
     *
     * @return array
     */
    public function errorInfo(): array
    {
        return $this->pdo->errorInfo();
    }
    /**
     * Execute an SQL statement and return the number of affected rows
     *
     * @param mixed ...$args
     * @return int
     */
    public function exec(...$args): int
    {
        return $this->pdo->exec(...$args);
    }
    /**
     * Retrieve a database connection attribute
     *
     * @param mixed ...$args
     * @return mixed
     */
    public function getAttribute(...$args)
    {
        return $this->pdo->getAttribute(...$args);
    }

    /**
     * Return an array of available PDO drivers
     *
     * @return array
     */
    public function getAvailableDrivers(): array
    {
        return $this->pdo->getAvailableDrivers();
    }
    /**
     * Checks if inside a transaction
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }
    /**
     * Returns the ID of the last inserted row or sequence value
     *
     * @param mixed ...$args
     * @return string
     */
    public function lastInsertId(...$args): string
    {
        return $this->pdo->lastInsertId(...$args);
    }
    /**
     * Prepares a statement for execution and returns a statement object
     *
     * @param mixed ...$args
     * @return \PDOStatement
     */
    public function prepare(...$args): \PDOStatement
    {
        return $this->pdo->prepare(...$args);
    }
    /**
     * Executes an SQL statement, returning a result set as a PDOStatement object
     *
     * @param mixed ...$args
     * @return \PDOStatement
     */
    public function query(...$args): \PDOStatement
    {
        return $this->pdo->query(...$args);
    }
    /**
     * Quotes a string for use in a query
     *
     * @param mixed ...$args
     * @return string
     */
    public function quote(...$args): string
    {
        return $this->pdo->quote(...$args);
    }

    /**
     * Rolls back a transaction
     *
     * @return bool
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }


    /**
     * Set an attribute
     *
     * @param int $attr
     * @param mixed $value
     * @return bool
     * @throws \Exception
     */
    public function setAttribute(int $attr, $value): bool
    {
        if ($attr === \PDO::ATTR_EMULATE_PREPARES) {
            throw new \Exception(
                'EasyDB does not allow the use of emulated prepared statements, which would be a security downgrade.'
            );
        }
        if ($attr === \PDO::ATTR_ERRMODE) {
            throw new \Exception(
                'EasyDB only allows the safest-by-default error mode (exceptions).'
            );
        }
        return $this->pdo->setAttribute($attr, $value);
    }

    /**
     * Make sure none of this array's elements are arrays
     *
     * @param array $params
     * @return bool
     */
    public function is1DArray(array $params): bool
    {
        return (
            \count($params) === \count($params, COUNT_RECURSIVE) &&
            \count(\array_filter($params, 'is_array')) < 1
        );
    }
}
