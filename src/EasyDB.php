<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB;

use \ParagonIE\EasyDB\Exception as Issues;
use \Throwable;

/**
 * Class EasyDB
 *
 * @package ParagonIE\EasyDB
 */
class EasyDB
{
    const DEFAULT_FETCH_STYLE = 0x31420000;

    /**
     * @var string
     */
    protected $dbEngine = '';

    /**
     * @var \PDO
     */
    protected $pdo;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var bool
     */
    protected $allowSeparators = false;

    /**
     * Dependency-Injectable constructor
     *
     * @param \PDO   $pdo
     * @param string $dbEngine
     * @param array  $options  Extra options
     */
    public function __construct(\PDO $pdo, string $dbEngine = '', array $options = [])
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
            $dbEngine = (string) $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        }

        $this->dbEngine = $dbEngine;
        $this->options = $options;
    }

    /**
     * Variadic version of $this->column()
     *
     * @param  string $statement SQL query without user data
     * @param  int    $offset    How many columns from the left are we grabbing
     *                           from each row?
     * @param  mixed  ...$params Parameters
     * @return mixed
     */
    public function col(string $statement, int $offset = 0, ...$params)
    {
        return $this->column($statement, $params, $offset);
    }

    /**
     * Fetch a column
     *
     * @param  string $statement SQL query without user data
     * @param  array  $params    Parameters
     * @param  int    $offset    How many columns from the left are we grabbing
     *                           from each row?
     * @return mixed
     */
    public function column(string $statement, array $params = [], int $offset = 0)
    {
        $stmt = $this->prepare($statement);
        if (!$this->is1DArray($params)) {
            throw new Issues\MustBeOneDimensionalArray(
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
     * @param  string $statement SQL query without user data
     * @param  mixed  ...$params Parameters
     * @return mixed
     */
    public function cell(string $statement, ...$params)
    {
        return $this->single($statement, $params);
    }


    /**
     * Delete rows in a database table.
     *
     * @param          string $table      Table name
     * @param          mixed  $conditions Defines the WHERE clause
     * @return         int
     * @throws         \InvalidArgumentException
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArgument
     * @throws         \TypeError
     */
    public function delete(string $table, $conditions): int
    {
        if ($conditions instanceof EasyStatement) {
            return $this->deleteWhereStatement($table, $conditions);
        } elseif (\is_array($conditions)) {
            return $this->deleteWhereArray($table, $conditions);
        } else {
            throw new Issues\MustBeArrayOrEasyStatement('Conditions must be an array or EasyStatement');
        }
    }

    /**
     * Delete rows in a database table.
     *
     * @param          string $table      Table name
     * @param          array  $conditions Defines the WHERE clause
     * @return         int
     * @throws         \InvalidArgumentException
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArgument
     * @throws         \TypeError
     */
    protected function deleteWhereArray(string $table, array $conditions): int
    {
        if (empty($table)) {
            throw new Issues\InvalidTableName(
                'Table name must be a non-empty string.'
            );
        }
        if (empty($conditions)) {
            // Don't allow foot-bullets
            return 0;
        }
        if (!$this->is1DArray($conditions)) {
            throw new Issues\MustBeOneDimensionalArray(
                'Only one-dimensional arrays are allowed.'
            );
        }
        /**
         * @var string $queryString
         */
        $queryString = 'DELETE FROM ' . $this->escapeIdentifier($table) . ' WHERE ';

        // Simple array for joining the strings together
        /**
         * @var array $params
         */
        $params = [];

        /**
         * @var array $arr
         */
        $arr = [];

        /**
         * @var string $i
         * @var string|int|bool|float|null $v
         */
        foreach ($conditions as $i => $v) {
            $i = $this->escapeIdentifier($i);
            if ($v === null) {
                $arr [] = " {$i} IS NULL ";
            } elseif (\is_bool($v)) {
                $arr []= $this->makeBooleanArgument($i, $v);
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
     * Delete rows in a database table.
     *
     * @param          string        $table      Table name
     * @param          EasyStatement $conditions Defines the WHERE clause
     * @return         int
     * @throws         \InvalidArgumentException
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArgument
     * @throws         \TypeError
     */
    protected function deleteWhereStatement(string $table, EasyStatement $conditions): int
    {
        if (empty($table)) {
            throw new Issues\InvalidTableName(
                'Table name must be a non-empty string.'
            );
        }
        if ($conditions->count() < 1) {
            // Don't allow foot-bullets
            return 0;
        }
        /**
         * @var string $queryString
         */
        $queryString = 'DELETE FROM ' . $this->escapeIdentifier($table) . ' WHERE ' . $conditions;

        /**
         * @var array $params
         */
        $params = [];
        /**
         * @var string|int|bool|float|null $v
         */
        foreach ($conditions->values() as $v) {
            $params[] = $v;
        }

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
     * @param  string $string Table or column name
     * @param  bool   $quote  Certain SQLs escape column names (i.e. mysql with `backticks`)
     * @return string
     */
    public function escapeIdentifier(string $string, bool $quote = true): string
    {
        if (empty($string)) {
            throw new Issues\InvalidIdentifier(
                'Invalid identifier: Must be a non-empty string.'
            );
        }
        switch ($this->dbEngine) {
            case 'sqlite':
                $patternWithSep = '/[^\.0-9a-zA-Z_\/]/';
                $patternWithoutSep = '/[^0-9a-zA-Z_\/]/';
                break;
            default:
                $patternWithSep = '/[^\.0-9a-zA-Z_]/';
                $patternWithoutSep = '/[^0-9a-zA-Z_]/';
        }

        // This behavior depends on whether or not separators are allowed.
        if ($this->allowSeparators) {
            $str = \preg_replace($patternWithSep, '', $string);
            if (\strpos($str, '.') !== false) {
                $pieces = \explode('.', $str);
                foreach ($pieces as $i => $p) {
                    $pieces[$i] = $this->escapeIdentifier($p, $quote);
                }
                return \implode('.', $pieces);
            }
        } else {
            $str = \preg_replace($patternWithoutSep, '', $string);
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

        // MySQL allows weirdly wrong column names:
        if ($this->dbEngine !== 'mysql') {
            // The first character cannot be [0-9]:
            if (\preg_match('/^[0-9]/', $str)) {
                throw new Issues\InvalidIdentifier(
                    'Invalid identifier: Must begin with a letter or underscore.'
                );
            }
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
     * @param          array  $values
     * @param          string $type
     * @return         string
     * @throws         \InvalidArgumentException
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArgument
     */
    public function escapeValueSet(array $values, string $type = 'string'): string
    {
        if (empty($values)) {
            // Default value: a sub-query that will return an empty set
            return '(SELECT 1 WHERE FALSE)';
        }
        // No arrays of arrays, please
        if (!$this->is1DArray($values)) {
            throw new Issues\MustBeOneDimensionalArray(
                'Only one-dimensional arrays are allowed.'
            );
        }
        // Build our array
        $join = [];
        /**
         * @var string|int $k
         * @var string|int|bool|float|null $v
         */
        foreach ($values as $k => $v) {
            switch ($type) {
                case 'int':
                    if (!\is_int($v)) {
                        throw new \InvalidArgumentException(
                            'Expected a integer at index ' .
                            (string) $k .
                            ' of argument 1 passed to ' .
                            static::class .
                            '::' .
                            __METHOD__ .
                            '(), received ' .
                            $this->getValueType($v)
                        );
                    }
                    $join[] = $v + 0;
                    break;
                case 'float':
                case 'decimal':
                case 'number':
                case 'numeric':
                    if (!\is_numeric($v)) {
                        throw new \InvalidArgumentException(
                            'Expected a number at index ' .
                            (string) $k .
                            ' of argument 1 passed to ' .
                            static::class .
                            '::' .
                            __METHOD__ .
                            '(), received ' .
                            $this->getValueType($v)
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
                            (string) $k .
                            ' of argument 1 passed to ' .
                            static::class .
                            '::' .
                            __METHOD__ .
                            '(), received ' .
                            $this->getValueType($v)
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
     * @param  string $value
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
     * @param          string $statement
     * @param          mixed  ...$params
     * @return         bool
     * @psalm-suppress MixedAssignment
     */
    public function exists(string $statement, ...$params): bool
    {
        /**
         * @var string|int|float|bool|null $result
         */
        $result = $this->single($statement, $params);
        return !empty($result);
    }

    /**
     * @param string $statement
     * @param mixed  ...$params
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
     * @param  string $table - table name
     * @param  array  $map   - associative array of which values should be assigned to each field
     * @return int
     * @throws \InvalidArgumentException
     * @throws \TypeError
     */
    public function insert(string $table, array $map): int
    {
        if (!empty($map)) {
            if (!$this->is1DArray($map)) {
                throw new Issues\MustBeOneDimensionalArray(
                    'Only one-dimensional arrays are allowed.'
                );
            }
        }

        list($queryString, $values) = $this->buildInsertQueryBoolSafe(
            $table,
            $map
        );
        /** @var string $queryString */
        /** @var array $values */

        return (int) $this->safeQuery(
            (string) $queryString,
            $values,
            \PDO::FETCH_BOTH,
            true
        );
    }

    /**
     * Insert a new record then get a particular field from the new row
     *
     * @param          string $table
     * @param          array  $map
     * @param          string $field
     * @return         mixed
     * @throws         \Exception
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArgument
     */
    public function insertGet(string $table, array $map, string $field)
    {
        if (empty($map)) {
            throw new \Exception('An empty array is not allowed for insertGet()');
        }
        if ($this->insert($table, $map) < 1) {
            throw new \Exception('Insert failed');
        }
        $post = [];
        $params = [];
        /**
         * @var string $i
         * @var string|bool|null|int|float $v
         */
        foreach ($map as $i => $v) {
            // Escape the identifier to prevent stupidity
            $i = $this->escapeIdentifier($i);
            if ($v === null) {
                $post []= " {$i} IS NULL ";
            } elseif (\is_bool($v)) {
                $post []= $this->makeBooleanArgument($i, $v);
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
     * @param          string $table - table name
     * @param          array  $maps  - array of associative array specifying values should be assigned to each field
     * @return         int
     * @throws         \InvalidArgumentException
     * @throws         Issues\QueryError
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArgument
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
        /**
         * @var array $first
         */
        $first = $maps[0];
        /**
         * @var array $map
         */
        foreach ($maps as $map) {
            if (!$this->is1DArray($map)) {
                throw new Issues\MustBeOneDimensionalArray(
                    'Every map in the second argument should have the same number of columns.'
                );
            }
        }

        $queryString = $this->buildInsertQuery($table, \array_keys($first));

        // Now let's run a query with the parameters
        $stmt = $this->prepare($queryString);
        $count = 0;
        /**
         * @var array $params
         */
        foreach ($maps as $params) {
            $stmt->execute(\array_values($params));
            $count += $stmt->rowCount();
        }
        return $count;
    }

    /**
     * Wrapper for insert() and lastInsertId()
     *
     * Do not use this with the pgsql driver. It is extremely unreliable.
     *
     * @param  string $table
     * @param  array  $map
     * @param  string $sequenceName (optional)
     * @return string
     * @throws Issues\QueryError
     * @throws \Exception
     */
    public function insertReturnId(string $table, array $map, string $sequenceName = '')
    {
        if ($this->dbEngine === 'pgsql') {
            throw new \Exception(
                'Do not use insertReturnId() with PostgreSQL. Use insertGet() instead, ' .
                'with an explicit column name rather than a sequence name.'
            );
        }
        if (!$this->insert($table, $map)) {
            throw new Issues\QueryError('Could not insert a new row into ' . $table . '.');
        }
        if ($sequenceName) {
            return $this->lastInsertId($sequenceName);
        }
        return $this->lastInsertId();
    }

    /**
     * Get an query string for an INSERT statement.
     *
     * @param string $table
     * @param array  $columns list of columns that will be inserted
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
                throw new Issues\MustBeOneDimensionalArray(
                    'Only one-dimensional arrays are allowed.'
                );
            }
        }

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
     * Get an query string for an INSERT statement.
     *
     * @param string $table
     * @param array  $map
     *
     * @return array {0: string, 1: array}
     *
     * @throws \InvalidArgumentException
     *   If $columns is not a one-dimensional array.
     */
    public function buildInsertQueryBoolSafe(string $table, array $map): array
    {
        /** @var array<int, string> $columns */
        $columns = [];
        /** @var array<int, string> $placeholders */
        $placeholders = [];
        /** @var array $values */
        $values = [];
        /**
         * @var string $key
         * @var string|bool|null $value
         */
        foreach ($map as $key => $value) {
            $columns[] = $key;
            if (\is_null($value)) {
                $placeholders[] = 'NULL';
            } elseif (\is_bool($value)) {
                if ($this->dbEngine === 'sqlite') {
                    $placeholders[] = $value ? "'1'" : "'0'";
                } else {
                    $placeholders[] = $value ? 'TRUE' : 'FALSE';
                }
            } else {
                $placeholders[] = '?';
                $values[] = $value;
            }
        }
        $columns = \array_map([$this, 'escapeIdentifier'], $columns);

        /** @var string $query */
        $query = \sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->escapeIdentifier($table),
            \implode(', ', $columns),
            \implode(', ', $placeholders)
        );
        return array($query, $values);
    }

    /**
     * Variadic shorthand for $this->safeQuery()
     *
     * @param  string $statement SQL query without user data
     * @param  mixed  ...$params Parameters
     * @return mixed
     * @throws \TypeError
     */
    public function q(string $statement, ...$params)
    {
        /**
         * @var array $result
         */
        $result = (array) $this->safeQuery(
            $statement,
            $params,
            self::DEFAULT_FETCH_STYLE,
            false,
            true
        );
        return $result;
    }

    /**
     * Similar to $this->q() except it only returns a single row
     *
     * @param  string $statement SQL query without user data
     * @param  mixed  ...$params Parameters
     * @return mixed
     * @throws \TypeError
     */
    public function row(string $statement, ...$params)
    {
        /**
         * @var array|int $result
         */
        $result = (array) $this->safeQuery(
            $statement,
            $params,
            self::DEFAULT_FETCH_STYLE,
            false,
            true
        );
        if (\is_array($result)) {
            return \array_shift($result);
        }
        return [];
    }

    /**
     * Variadic shorthand for $this->safeQuery()
     *
     * @param  string $statement SQL query without user data
     * @param  mixed  ...$params Parameters
     * @return mixed - If successful, a 2D array
     * @throws \TypeError
     */
    public function run(string $statement, ...$params)
    {
        return $this->safeQuery($statement, $params, self::DEFAULT_FETCH_STYLE, false, true);
    }

    /**
     * Perform a Parametrized Query
     *
     * @param  string $statement         The query string (hopefully untainted
     *                                  by user input)
     * @param  array  $params            The parameters (used in prepared
     *                                   statements)
     * @param  int    $fetchStyle        PDO::FETCH_STYLE
     * @param  bool   $returnNumAffected Return the number of rows affected?
     * @param  bool   $calledWithVariadicParams Indicates method is being invoked from variadic $params method
     * @return array|int|object
     * @throws \InvalidArgumentException
     * @throws Issues\QueryError
     * @throws \TypeError
     */
    public function safeQuery(
        string $statement,
        array $params = [],
        int $fetchStyle = self::DEFAULT_FETCH_STYLE,
        bool $returnNumAffected = false,
        bool $calledWithVariadicParams = false
    ) {
        if ($fetchStyle === self::DEFAULT_FETCH_STYLE) {
            if (isset($this->options[\PDO::ATTR_DEFAULT_FETCH_MODE])) {
                /**
                 * @var int $fetchStyle
                 */
                $fetchStyle = $this->options[\PDO::ATTR_DEFAULT_FETCH_MODE];
            } else {
                $fetchStyle = \PDO::FETCH_ASSOC;
            }
        }

        if (empty($params)) {
            $stmt = $this->pdo->query($statement);
            if ($returnNumAffected) {
                return (int) $stmt->rowCount();
            }
            return $this->getResultsStrictTyped($stmt, $fetchStyle);
        }
        if (!$this->is1DArray($params)) {
            if ($calledWithVariadicParams) {
                throw new Issues\MustBeOneDimensionalArray(
                    'Only one-dimensional arrays are allowed, please use ' .
                    __METHOD__ .
                    '()'
                );
            }

            throw new Issues\MustBeOneDimensionalArray(
                'Only one-dimensional arrays are allowed.'
            );
        }
        $stmt = $this->prepare($statement);
        $stmt->execute($params);
        if ($returnNumAffected) {
            return (int) $stmt->rowCount();
        }
        return $this->getResultsStrictTyped($stmt, $fetchStyle);
    }

    /**
     * Fetch a single result -- useful for SELECT COUNT() queries
     *
     * @param  string $statement
     * @param  array  $params
     * @return mixed
     * @throws \InvalidArgumentException
     * @throws Issues\QueryError
     */
    public function single(string $statement, array $params = [])
    {
        if (!$this->is1DArray($params)) {
            throw new Issues\MustBeOneDimensionalArray(
                'Only one-dimensional arrays are allowed.'
            );
        }
        $stmt = $this->prepare($statement);
        $stmt->execute($params);
        return $stmt->fetchColumn(0);
    }

    /**
     * Update a row in a database table.
     *
     * @param  string $table      Table name
     * @param  array  $changes    Associative array of which values should be
     *                            assigned to each field
     * @param  mixed  $conditions WHERE clause
     * @return int
     * @throws \InvalidArgumentException
     * @throws Issues\QueryError
     *
     * @throws \TypeError
     */
    public function update(string $table, array $changes, $conditions): int
    {
        if (empty($table)) {
            throw new Issues\InvalidTableName(
                'Table name must be a non-empty string.'
            );
        }
        if ($conditions instanceof EasyStatement) {
            return $this->updateWhereStatement($table, $changes, $conditions);
        } elseif (\is_array($conditions)) {
            return $this->updateWhereArray($table, $changes, $conditions);
        } else {
            throw new Issues\MustBeArrayOrEasyStatement('Conditions must be an array or instance of EasyStatement');
        }
    }

    /**
     * Update a row in a database table.
     *
     * @param  string $table      Table name
     * @param  array  $changes    Associative array of which values should be
     *                           assigned to each field
     * @param  array  $conditions WHERE clause
     * @return int
     * @throws \InvalidArgumentException
     * @throws Issues\QueryError
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArgument
     * @throws         \TypeError
     */
    protected function updateWhereArray(string $table, array $changes, array $conditions): int
    {
        if (empty($changes) || empty($conditions)) {
            return 0;
        }
        if (!$this->is1DArray($changes) || !$this->is1DArray($conditions)) {
            throw new Issues\MustBeOneDimensionalArray(
                'Only one-dimensional arrays are allowed.'
            );
        }
        /**
         * @var string $queryString
         */
        $queryString = 'UPDATE ' . $this->escapeIdentifier($table) . ' SET ';
        /**
         * @var array $params
         */
        $params = [];

        // The first set (pre WHERE)
        /**
         * @var array $pre
         */
        $pre = [];
        /**
         * @var string $i
         * @var string|int|bool|float|null $v
         */
        foreach ($changes as $i => $v) {
            $i = $this->escapeIdentifier($i);
            if ($v === null) {
                $pre []= " {$i} = NULL";
            } elseif (\is_bool($v)) {
                $pre []= $this->makeBooleanArgument($i, $v);
            } else {
                $pre []= " {$i} = ?";
                $params[] = $v;
            }
        }
        $queryString .= \implode(', ', $pre);
        $queryString .= " WHERE ";

        // The last set (post WHERE)
        $post = [];
        /**
         * @var string $i
         * @var string|int|bool|float|null $v
         */
        foreach ($conditions as $i => $v) {
            $i = $this->escapeIdentifier($i);
            if ($v === null) {
                $post []= " {$i} IS NULL";
            } elseif (\is_bool($v)) {
                $post []= $this->makeBooleanArgument($i, $v);
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
     * Update a row in a database table.
     *
     * @param  string        $table      Table name
     * @param  array         $changes    Associative array of which values
     *                                   should be assigned to each field
     * @param  EasyStatement $conditions WHERE clause
     * @return int
     * @throws \InvalidArgumentException
     * @throws Issues\QueryError
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArgument
     * @throws         \TypeError
     */
    protected function updateWhereStatement(
        string $table,
        array $changes,
        EasyStatement $conditions
    ): int {
        if (empty($changes) || $conditions->count() < 1) {
            return 0;
        }
        $queryString = 'UPDATE ' . $this->escapeIdentifier($table) . ' SET ';
        $params = [];

        // The first set (pre WHERE)
        $pre = [];
        /**
         * @var string $i
         * @var string|int|bool|float|null $v
         */
        foreach ($changes as $i => $v) {
            $i = $this->escapeIdentifier($i);
            if ($v === null) {
                $pre []= " {$i} = NULL";
            } elseif (\is_bool($v)) {
                $pre []= $this->makeBooleanArgument($i, $v);
            } else {
                $pre []= " {$i} = ?";
                $params[] = $v;
            }
        }

        $queryString .= \implode(', ', $pre);
        $queryString .= " WHERE {$conditions}";
        /**
         * @var string|int|bool|float|null $v
         */
        foreach ($conditions->values() as $v) {
            $params[] = $v;
        }

        return (int) $this->safeQuery(
            $queryString,
            $params,
            \PDO::FETCH_BOTH,
            true
        );
    }

    /**
     * @param bool $value
     * @return self
     */
    public function setAllowSeparators(bool $value): self
    {
        $this->allowSeparators = $value;
        return $this;
    }


    /**
     * Make sure none of this array's elements are arrays
     *
     * @param  array $params
     * @return bool
     */
    public function is1DArray(array $params): bool
    {
        return (
            \count($params) === \count($params, COUNT_RECURSIVE) &&
            \count(\array_filter($params, 'is_array')) < 1
        );
    }

    /**
     * Try to execute a callback within the scope of a flat transaction
     * If already inside a transaction, does not start a new one.
     * Callable should accept one parameter, i.e. function (EasyDB $db) {}
     *
     * @param callable $callback
     *
     * @return mixed
     *
     * @throws Throwable
     */
    public function tryFlatTransaction(callable $callback)
    {
        $autoStartTransaction = $this->inTransaction() === false;

        // If we're starting a transaction, we don't need to catch here
        if ($autoStartTransaction) {
            $this->beginTransaction();
        }
        try {
            /**
            * @var string|int|bool|float|null|array|object|resource $out
            */
            $out = $callback($this);
            // If we started the transaction, we should commit here
            if ($autoStartTransaction) {
                $this->commit();
            }

            return $out;
        } catch (Throwable $e) {
            // If we started the transaction, we should cleanup here
            if ($autoStartTransaction) {
                $this->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Get the type of a variable.
     *
     * @param  mixed $v
     * @return string
     */
    protected function getValueType($v = null): string
    {
        if (\is_scalar($v) || \is_array($v)) {
            return (string) \gettype($v);
        }
        if (\is_object($v)) {
            return 'an instance of ' . \get_class($v);
        }
        return (string) \var_export($v, true);
    }

    /**
     * Helper for PDOStatement::fetchAll() that always returns an array or object.
     *
     * @param  \PDOStatement $stmt
     * @param  int           $fetchStyle
     * @return array|object
     * @throws \TypeError
     */
    protected function getResultsStrictTyped(\PDOStatement $stmt, int $fetchStyle = \PDO::FETCH_ASSOC)
    {
        /**
         * @var array|object|bool $results
         */
        $results = $stmt->fetchAll($fetchStyle);
        if (\is_array($results)) {
            return $results;
        } elseif (\is_object($results)) {
            return $results;
        }
        throw new \TypeError('Unexpected return type: ' . $this->getValueType($results));
    }

    /**
     * @param string $column
     * @param bool   $value
     * @return string
     */
    protected function makeBooleanArgument(string $column, bool $value): string
    {
        if ($value === true) {
            if ($this->dbEngine === 'sqlite') {
                return " {$column} = 1 ";
            } else {
                return " {$column} = TRUE ";
            }
        }
        if ($this->dbEngine === 'sqlite') {
            return " {$column} = 0 ";
        } else {
            return " {$column} = FALSE ";
        }
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
     * @param  string ...$args
     * @return int
     */
    public function exec(...$args): int
    {
        return $this->pdo->exec(...$args);
    }

    /**
     * Retrieve a database connection attribute
     *
     * @param  int ...$args
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
     * @param  string ...$args
     * @return string
     */
    public function lastInsertId(...$args): string
    {
        return $this->pdo->lastInsertId(...$args);
    }

    /**
     * Prepares a statement for execution and returns a statement object
     *
     * @param  string ...$args
     * @return \PDOStatement
     * @throws Issues\QueryError
     */
    public function prepare(...$args): \PDOStatement
    {
        $trimmed = trim($args[0]);
        if (empty($trimmed)) {
            throw new Issues\QueryError(
                "Empty query passed to prepare()"
            );
        }
        return $this->pdo->prepare(...$args);
    }

    /**
     * Executes an SQL statement, returning a result set as a PDOStatement object
     *
     * @param  string ...$args
     * @return \PDOStatement
     */
    public function query(...$args): \PDOStatement
    {
        return $this->pdo->query(...$args);
    }

    /**
     * Quotes a string for use in a query
     *
     * @param  string ...$args
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
     * @param  int   $attr
     * @param  mixed $value
     * @return bool
     * @throws \Exception
     */
    public function setAttribute(int $attr, $value): bool
    {
        if ($attr === \PDO::ATTR_EMULATE_PREPARES) {
            if ($value !== false) {
                throw new \Exception(
                    'EasyDB does not allow the use of emulated prepared statements, ' .
                    'which would be a security downgrade.'
                );
            }
        }
        if ($attr === \PDO::ATTR_ERRMODE) {
            if ($value !== \PDO::ERRMODE_EXCEPTION) {
                throw new \Exception(
                    'EasyDB only allows the safest-by-default error mode (exceptions).'
                );
            }
        }
        return $this->pdo->setAttribute($attr, $value);
    }
}
