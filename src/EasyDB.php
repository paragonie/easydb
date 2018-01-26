<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB;

use \ParagonIE\EasyDB\Exception as Issues;

/**
 * EasyDB.
 *
 * @package ParagonIE\EasyDB
 */
class EasyDB
{
    /**
     * @const DEFAULT_FETCH_STYLE The default fetch style.
     */
    const DEFAULT_FETCH_STYLE = 0x31420000;
    
    /**
     * @var string $dbEngine The database engine.
     */
    protected $dbEngine = '';

    /**
     * @var object $pdo The PDO object.
     */
    protected $pdo;

    /**
     * @var array $options An array that contains the database options.
     */
    protected $options = [];

    /**
     * @var bool $allowSeparators Are we allowing separators.
     */
    protected $allowSeparators = false;

    /**
     * Dependency-Injectable constructor.
     *
     * @param object $pdo      The PDO object.
     * @param string $dbEngine The database engine.
     * @param array $options   All the wonderful options.
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
     * Variadic version of $this->column().
     *
     * @param string $statement SQL query without user data.
     * @param int $offset       How many columns from the left are we grabbing
     *                          from each row?
     * @param mixed ...$params  The list of parameters.
     *
     * @return mixed Return a list of columns.
     */
    public function col(string $statement, int $offset = 0, ...$params)
    {
        return $this->column($statement, $params, $offset);
    }

    /**
     * Fetch a column
     *
     * @param string $statement SQL query without user data.
     * @param array $params     The list of parameters
     * @param int $offset       How many columns from the left are we grabbing
     *                          from each row?
     *
     * @throws InvalidArgumentException If the array is not one-dimensional. 
     *
     * @return mixed Return a list of columns.
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
     * @param mixed ...$params  The list of parameters.
     *
     * @return mixed.
     */
    public function cell(string $statement, ...$params)
    {
        return $this->single($statement, $params);
    }

    /**
     * Delete rows in a database table.
     *
     * @param string $table     The table name.
     * @param array $conditions Defines the WHERE clause.
     *
     * @throws InvalidArgumentException If the table name is empty.
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArgument
     *
     * @return int 
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
     * Make sure only valid characters make it in column/table names.
     *
     * @ref <https://stackoverflow.com/questions/10573922/what-does-the-sql-standard-say-about-usage-of-backtick>.
     *
     * @param string $string Table or column name.
     * @param bool $quote    Certain SQLs escape column names (i.e. mysql with `backticks`).
     *
     * @throws InvalidIdentifier If the identifier is empty.
     * @throws InvalidIdentifier If the identifier contains separators (.).
     * @throws InvalidIdentifier If the identifier contains invalid characters.
     * @throws InvalidIdentifier If the first character is not a letter/underscore.
     *
     * @return string Return the escaped identifier.
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
     * Input: ([1, 2, 3, 5], int).
     * Output: "(1,2,3,5)".
     *
     * @param array $values A list of values.
     * @param string $type  What's the data type.
     *
     * @throws InvalidArgumentException If the array is not one-dimensional.
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArgument
     *
     * @return string Return the escaped value set.
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
                                (string) $k .
                            ' of argument 1 passed to ' .
                                (string) static::class .
                            '::' .
                                __METHOD__ .
                            '(), received ' .
                            $this->getValueType($v)
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
                                (string) $k .
                            ' of argument 1 passed to ' .
                                (string) static::class .
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
                                (string) static::class .
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
     * Input: ("string_not%escaped").
     * Output: "string\_not\%escaped".
     *
     * WARNING: This function always escapes wildcards using backslash!
     *
     * @param string $value The like value.
     *
     * @return string Return the escaped like value.
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
     * @param string $statement.
     * @param mixed ...$params The list of parameters.
     *
     * @psalm-suppress MixedAssignment
     *
     * @return bool If it actually exists.
     */
    public function exists(string $statement, ...$params): bool
    {
        $result = $this->single($statement, $params);
        return !empty($result);
    }

    /**
     * Get the first column.
     *
     * @param string $statement.
     * @param mixed ...$params The list of parameters.
     *
     * @return mixed.
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
     * Get the PDO object.
     *
     * @return object Return the PDO object. (to prevent it from being modified
     *                to disable safety/security features).
     */
    public function getPdo(): \PDO
    {
        return $this->pdo;
    }

    /**
     * Insert a new row to a table in a database.
     *
     * @param string $table The table name.
     * @param array $map    An associative array of which values should be
     *                      assigned to each field.
     *
     * @throws InvalidArgumentException If the array is not one-dimensional. 
     *
     * @return int
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
     * Insert a new record then get a particular field from the new row.
     *
     * @param string $table The table name.
     * @param array $map    An associative array of which values should be
     *                      assigned to each field.
     * @param string $field A creative field name.
     *
     * @throws Exception If an empty array was passed.
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArgument
     *
     * @return mixed.
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
     * Insert many new rows to a table in a database. using the same prepared statement.
     *
     * @param string $table The table name.
     * @param array $maps   An associative array specifying values that
     *                      should be assigned to each field.
     *
     * @throws InvalidArgumentException If no fields were passed in $map.
     * @throws QueryError               If the map does not have the same number
     *                                  as the columns.
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArgument
     *
     * @return int
     */
    public function insertMany(string $table, array $maps): int
    {
        if (\count($maps) < 1) {
            throw new \InvalidArgumentException(
                'Argument 2 passed to ' .
                    (string) static::class .
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
     * Wrapper for insert() and lastInsertId().
     *
     * Do not use this with the pgsql driver. It is extremely unreliable.
     *
     * @param string $table        The table name.
     * @param array $map           An associative array specifying values that
     *                             should be assigned to each field.
     * @param string $sequenceName This is absolutly optional.
     *
     * @throws QueryError
     * @throws Exception
     *
     * @return string.
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
            return (string) $this->lastInsertId($sequenceName);
        }
        return (string) $this->lastInsertId();
    }

    /**
     * Get an query string for an INSERT statement.
     *
     * @param string $table  The table name.
     * @param array $columns list of columns that will be inserted.
     *
     * @throws InvalidArgumentException If the array is not one-dimensional.
     *
     * @return string
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
     * Variadic shorthand for $this->safeQuery().
     *
     * @param string $statement SQL query without user data.
     * @param mixed ...$params  The list of parameters.
     *
     * @return mixed
     */
    public function q(string $statement, ...$params)
    {
        /** @var array $result */
        $result = (array) $this->safeQuery($statement, $params);
        
        return $result;
    }

    /**
     * Similar to $this->q() except it only returns a single row.
     *
     * @param string $statement SQL query without user data.
     * @param mixed ...$params  The list of parameters.
     *
     * @return mixed.
     */
    public function row(string $statement, ...$params)
    {
        /** @var array $result */
        $result = (array) $this->safeQuery($statement, $params);
        
        if (\is_array($result)) {
            return \array_shift($result);
        }
        
        return [];
    }

    /**
     * Variadic shorthand for $this->safeQuery()
     *
     * @param string $statement SQL query without user data.
     * @param mixed ...$params  The list of parameters.
     *
     * @return mixed Return successful, a 2D array.
     */
    public function run(string $statement, ...$params)
    {
        return $this->safeQuery($statement, $params);
    }

    /**
     * Perform a Parametrized Query.
     *
     * @param string $statement       The query string (hopefully untainted
     *                                by user input).
     * @param array $params           The parameters (used in prepared
     *                                statements).
     * @param int $fetchStyle         The PDO fetch style.
     * @param bool $returnNumAffected Return the number of rows affected?
     *
     * @throws InvalidArgumentException If the array is not one-dimensional.            
     *
     * @return mixed.
     */
    public function safeQuery(
        string $statement,
        array $params = [],
        int $fetchStyle = self::DEFAULT_FETCH_STYLE,
        bool $returnNumAffected = false
    ) {
        if ($fetchStyle === self::DEFAULT_FETCH_STYLE) {
            if (isset($this->options[\PDO::ATTR_DEFAULT_FETCH_MODE])) {
                /** @var int $fetchStyle */
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
            throw new \InvalidArgumentException(
                'Only one-dimensional arrays are allowed.'
            );
        }
        
        $stmt = $this->pdo->prepare($statement);
        $stmt->execute($params);
        
        if ($returnNumAffected) {
            return (int) $stmt->rowCount();
        }
        
        return $this->getResultsStrictTyped($stmt, $fetchStyle);
    }

    /**
     * Fetch a single result -- useful for SELECT COUNT() queries.
     *
     * @param string $statement.
     * @param array  $params    The list of parameters.
     *
     * @throws InvalidArgumentException
     *
     * @return mixed.
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
     * @param string $table     The table name.
     * @param array $changes    An associative array of which the values should be
     *                          assigned to each field
     * @param array $conditions The WHERE clause.
     *
     * @throws InvalidArgumentException If the array is not one-dimensional.
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArgument
     *
     * @return int.
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
     * Set the allowed separators.
     *
     * @param bool $value The LIST.
     *
     * @return self Return me.
     */
    public function setAllowSeparators(bool $value): self
    {
        $this->allowSeparators = $value;
        return $this;
    }


    /**
     * Make sure none of this array's elements are arrays.
     *
     * @param array $params The list of parameters.
     *
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
     * Callable should accept one parameter, i.e. function (EasyDB $db) {}.
     *
     * @param callable $callback The amazing callback.
     *
     * @return bool.
     */
    public function tryFlatTransaction(callable $callback): bool
    {
        $autoStartTransaction = $this->inTransaction() === false;

        // If we're starting a transaction, we don't need to catch here
        if ($autoStartTransaction) {
            $this->beginTransaction();
        }
        
        try {
            $callback($this);
            // If we started the transaction, we should commit here
            
            if ($autoStartTransaction) {
                $this->commit();
            }
        } catch (\Throwable $e) {
            // If we started the transaction, we should cleanup here
            
            if ($autoStartTransaction) {
                $this->rollBack();
            }

            throw $e;
        }

        return true;
    }

    /**
     * Get the type of a variable.
     *
     * @param mixed $v The variable to test.
     *
     * @return string.
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
     * @param object $stmt    The PDO statement.
     * @param int $fetchStyle The fetch style.
     *
     * @throws TypeError If the return type is unexpected.
     *
     * @return mixed
     */
    protected function getResultsStrictTyped(\PDOStatement $stmt, int $fetchStyle = \PDO::FETCH_ASSOC)
    {
        /** @var array|object $results */
        $results = $stmt->fetchAll($fetchStyle);
        
        if (\is_array($results)) {
            return (array) $results;
        } elseif (\is_object($results)) {
            return (object) $results;
        }
        
        throw new \TypeError('Unexpected return type: ' . $this->getValueType($results));
    }

    /**
     * Make a boolean argument.
     *
     * @param string $column The column.
     * @param bool $value    The value.
     *
     * @return string.
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
     * Initiates a transaction.
     *
     * @return bool.
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commits a transaction.
     *
     * @return bool.
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Fetch the SQLSTATE associated with the last operation on the database
     * handle.
     *
     * @return mixed.
     */
    public function errorCode()
    {
        return $this->pdo->errorCode();
    }

    /**
     * Fetch extended error information associated with the last operation on
     * the database handle.
     *
     * @return array.
     */
    public function errorInfo(): array
    {
        return $this->pdo->errorInfo();
    }

    /**
     * Execute an SQL statement and return the number of affected rows.
     *
     * @param mixed ...$args The arguments.
     *
     * @return int.
     */
    public function exec(...$args): int
    {
        return $this->pdo->exec(...$args);
    }

    /**
     * Retrieve a database connection attribute.
     *
     * @param mixed ...$args The arguments.
     *
     * @return mixed.
     */
    public function getAttribute(...$args)
    {
        return $this->pdo->getAttribute(...$args);
    }

    /**
     * Return an array of available PDO drivers.
     *
     * @return array.
     */
    public function getAvailableDrivers(): array
    {
        return $this->pdo->getAvailableDrivers();
    }

    /**
     * Checks if inside a transaction.
     *
     * @return bool.
     */
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @param mixed ...$args The arguments.
     *
     * @return string.
     */
    public function lastInsertId(...$args): string
    {
        return $this->pdo->lastInsertId(...$args);
    }

    /**
     * Prepares a statement for execution and returns a statement object.
     *
     * @param mixed ...$args The arguments.
     *
     * @return object Return the PDO statement.
     */
    public function prepare(...$args): \PDOStatement
    {
        return $this->pdo->prepare(...$args);
    }

    /**
     * Executes an SQL statement, returning a result set as a PDOStatement object.
     *
     * @param mixed ...$args The arguments.
     *
     * @return object The PDO statement.
     */
    public function query(...$args): \PDOStatement
    {
        return $this->pdo->query(...$args);
    }

    /**
     * Quotes a string for use in a query.
     *
     * @param mixed ...$args The arguments.
     *
     * @return string.
     */
    public function quote(...$args): string
    {
        return $this->pdo->quote(...$args);
    }

    /**
     * Rolls back a transaction.
     *
     * @return bool.
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Set an attribute.
     *
     * @param int $attr    The attribute.
     * @param mixed $value The value.
     *
     * @throws Exception.
     *
     * @return bool.
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
