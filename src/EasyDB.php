<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB;

use ParagonIE\EasyDB\Exception\{
    EasyDBException,
    InvalidIdentifier,
    InvalidTableName,
    MustBeOneDimensionalArray,
    QueryError
};
use PDO;
use PDOStatement;
use InvalidArgumentException;
use Throwable;
use TypeError;
use function
    array_fill,
    array_filter,
    array_keys,
    array_map,
    array_merge,
    array_push,
    array_values,
    count,
    explode,
    gettype,
    get_class,
    implode,
    is_array,
    is_bool,
    is_int,
    is_null,
    is_numeric,
    is_object,
    is_scalar,
    is_string,
    preg_replace,
    sprintf,
    str_contains,
    var_export;

/**
 * Class EasyDB
 *
 * @package ParagonIE\EasyDB
 */
class EasyDB
{
    const DEFAULT_FETCH_STYLE = 0x31420000;

    protected string $dbEngine = '';
    protected PDO $pdo;
    protected array $options = [];
    protected bool $allowSeparators = false;

    /**
     * Dependency-Injectable constructor
     *
     * @param PDO    $pdo
     * @param string $dbEngine
     * @param array  $options  Extra options
     */
    public function __construct(PDO $pdo, string $dbEngine = '', array $options = [])
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(
            PDO::ATTR_EMULATE_PREPARES,
            false
        );
        $this->pdo->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );

        if (empty($dbEngine)) {
            $dbEngine = (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
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
     * @param  scalar|null|object ...$params Parameters
     * @return array|false
     *
     * @psalm-taint-sink sql $statement
     */
    public function col(string $statement, int $offset = 0, ...$params): array|bool
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
     * @return array|false
     *
     * @psalm-taint-sink sql $statement
     */
    public function column(string $statement, array $params = [], int $offset = 0): array|bool
    {
        $stmt = $this->prepare($statement);
        if (!$this->is1DArray($params)) {
            throw new MustBeOneDimensionalArray(
                'Only one-dimensional arrays are allowed.'
            );
        }
        $stmt->execute($params);
        return $stmt->fetchAll(
            PDO::FETCH_COLUMN,
            $offset
        );
    }

    /**
     * Variadic version of $this->single()
     *
     * @param  string $statement SQL query without user data
     * @param  scalar|null|object ...$params Parameters
     * @return scalar|null
     *
     * @psalm-taint-sink sql $statement
     */
    public function cell(
        string $statement,
        float|object|bool|int|string|null ...$params
    ): float|bool|int|string|null {
        return $this->single($statement, $params);
    }

    /**
     * Alternative to run() that returns the keys as the first row, then
     * the values in all subsequent rows.
     *
     * @param  string $statement SQL query without user data
     * @param  scalar|null|object  ...$params Parameters
     * @return array[] - If successful, a 2D array
     *
     * @throws TypeError
     *
     * @psalm-taint-sink sql $statement
     */
    public function csv(string $statement, float|bool|int|string|null|object ...$params): array
    {
        /** @var array<int, array<string, scalar>> $results */
        $results = $this->safeQuery(
            $statement,
            $params,
            self::DEFAULT_FETCH_STYLE,
            false,
            true
        );
        if (empty($results)) {
            /* Array containing an array of empty keys and no subsequent rows */
            return [[]];
        }
        $mapping = [];
        array_push($mapping, array_keys($results[0]));
        foreach ($results as $row) {
            array_push($mapping, array_values($row));
        }
        return $mapping;
    }

    /**
     * Delete rows in a database table.
     *
     * @param  string $table                   Table name
     * @param  EasyStatement|array $conditions Defines the WHERE clause
     * @return int
     *
     * @throws TypeError
     *
     * @psalm-taint-source input $table
     */
    public function delete(string $table, EasyStatement|array $conditions): int
    {
        if ($conditions instanceof EasyStatement) {
            return $this->deleteWhereStatement($table, $conditions);
        }
        return $this->deleteWhereArray($table, $conditions);
    }

    /**
     * Delete rows in a database table.
     *
     * @param  string $table      Table name
     * @param  array  $conditions Defines the WHERE clause
     * @return int
     *
     * @throws InvalidTableName
     * @throws MustBeOneDimensionalArray
     * @throws TypeError
     *
     * @psalm-taint-source input $table
     */
    protected function deleteWhereArray(string $table, array $conditions): int
    {
        if (empty($table)) {
            throw new InvalidTableName(
                'Table name must be a non-empty string.'
            );
        }
        if (empty($conditions)) {
            // Don't allow foot-bullets
            return 0;
        }
        if (!$this->is1DArray($conditions)) {
            throw new MustBeOneDimensionalArray(
                'Only one-dimensional arrays are allowed.'
            );
        }

        /** @psalm-taint-escape sql */
        $queryString = 'DELETE FROM ' . $this->escapeIdentifier($table) . ' WHERE ';

        // Simple array for joining the strings together
        $params = [];
        $placeholders = [];
        /**
         * @var string $i
         * @var string|int|bool|float|null $v
         */
        foreach ($conditions as $i => $v) {
            /** @psalm-taint-escape sql */
            $i = $this->escapeIdentifier($i);
            if ($v === null) {
                $placeholders [] = " {$i} IS NULL ";
            } elseif (is_bool($v)) {
                $placeholders []= $this->makeBooleanArgument($i, $v);
            } else {
                $placeholders []= " {$i} = ? ";
                $params[] = $v;
            }
        }
        $queryString .= implode(' AND ', $placeholders);

        return (int) $this->safeQuery(
            $queryString,
            $params,
            PDO::FETCH_BOTH,
            true
        );
    }


    /**
     * Delete rows in a database table.
     *
     * @param  string        $table      Table name
     * @param  EasyStatement $conditions Defines the WHERE clause
     * @return int
     *
     * @throws InvalidTableName
     *
     * @psalm-taint-source input $table
     */
    protected function deleteWhereStatement(string $table, EasyStatement $conditions): int
    {
        if (empty($table)) {
            throw new InvalidTableName(
                'Table name must be a non-empty string.'
            );
        }
        if ($conditions->count() < 1) {
            // Don't allow foot-bullets
            return 0;
        }
        /** @psalm-taint-escape sql */
        $queryString = 'DELETE FROM ' . $this->escapeIdentifier($table) . ' WHERE ' . $conditions;
        $params = [];

        /**
         * @var ?scalar $v
         */
        foreach ($conditions->values() as $v) {
            $params[] = $v;
        }

        return (int) $this->safeQuery(
            $queryString,
            $params,
            PDO::FETCH_BOTH,
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
     *
     * @throws InvalidIdentifier
     *
     * @psalm-taint-source input $string
     */
    public function escapeIdentifier(string $string, bool $quote = true): string
    {
        if (empty($string)) {
            throw new InvalidIdentifier(
                'Invalid identifier: Must be a non-empty string.'
            );
        }
        switch ($this->dbEngine) {
            case 'sqlite':
                $patternWithSep = '/[^.0-9a-zA-Z_\/]/';
                $patternWithoutSep = '/[^0-9a-zA-Z_\/]/';
                break;
            default:
                $patternWithSep = '/[^.0-9a-zA-Z_]/';
                $patternWithoutSep = '/[^0-9a-zA-Z_]/';
        }

        // This behavior depends on whether or not separators are allowed.
        if ($this->allowSeparators) {
            $str = preg_replace($patternWithSep, '', $string);
            if (str_contains($str, '.')) {
                $pieces = explode('.', $str);
                foreach ($pieces as $i => $p) {
                    /** @psalm-taint-escape sql */
                    $pieces[$i] = $this->escapeIdentifier($p, $quote);
                }
                return implode('.', $pieces);
            }
        } else {
            $str = preg_replace($patternWithoutSep, '', $string);
            if ($str !== trim($string)) {
                if ($str === str_replace('.', '', $string)) {
                    throw new InvalidIdentifier(
                        'Separators (.) are not permitted.'
                    );
                }
                throw new InvalidIdentifier(
                    'Invalid identifier: Invalid characters supplied.'
                );
            }
        }

        // MySQL allows weirdly wrong column names:
        if ($this->dbEngine !== 'mysql') {
            // The first character cannot be [0-9]:
            if (preg_match('/^[0-9]/', $str)) {
                throw new InvalidIdentifier(
                    'Invalid identifier: Must begin with a letter or underscore.'
                );
            }
        }

        if ($quote) {
            return match ($this->dbEngine) {
                'mssql' => '[' . $str . ']',
                'mysql' => '`' . $str . '`',
                default => '"' . $str . '"',
            };
        }
        return $str;
    }

    /**
     * Create a parenthetical statement e.g. for NOT IN queries.
     *
     * Input: ([1, 2, 3, 5], int)
     * Output: "(1,2,3,5)"
     *
     * @param  array  $values
     * @param  string $type
     * @return string
     *
     * @throws InvalidArgumentException
     * @throws MustBeOneDimensionalArray
     *
     * @psalm-taint-source input $values
     */
    public function escapeValueSet(array $values, string $type = 'string'): string
    {
        if (empty($values)) {
            // Default value: a sub-query that will return an empty set
            return '(SELECT 1 WHERE FALSE)';
        }
        // No arrays of arrays, please
        if (!$this->is1DArray($values)) {
            throw new MustBeOneDimensionalArray(
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
                    if (!is_int($v)) {
                        throw new InvalidArgumentException(
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
                    if (!is_numeric($v)) {
                        throw new InvalidArgumentException(
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
                    if (is_numeric($v)) {
                        $v = (string) $v;
                    }
                    if (!is_string($v)) {
                        throw new InvalidArgumentException(
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
                    $join[] = $this->pdo->quote($v, PDO::PARAM_STR);
                    break;
                default:
                    break 2;
            }
        }
        if (empty($join)) {
            return '(SELECT 1 WHERE FALSE)';
        }
        return '(' . implode(', ', $join) . ')';
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
     *
     * @psalm-taint-source input $value
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
     *
     * @psalm-taint-sink sql $statement
     */
    public function exists(string $statement, ...$params): bool
    {
        $result = $this->single($statement, $params);
        return !empty($result);
    }

    /**
     * @param string $statement
     * @param scalar|null|object ...$params
     * @return array|false
     *
     * @psalm-taint-sink sql $statement
     */
    public function first(string $statement, ...$params): array|bool
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
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Insert a new row to a table in a database.
     *
     * @param  string $table - table name
     * @param  array  $map   - associative array of which values should be assigned to each field
     * @return int
     *
     * @throws MustBeOneDimensionalArray
     *
     * @psalm-param array<string, scalar|EasyPlaceholder|null> $map
     *
     * @psalm-taint-source input $table
     */
    public function insert(string $table, array $map): int
    {
        if (!empty($map)) {
            if (!$this->is1DArray($map)) {
                throw new MustBeOneDimensionalArray(
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
            $queryString,
            $values,
            PDO::FETCH_BOTH,
            true
        );
    }

    /**
     * Insert a row into the table, ignoring on key collisions
     *
     * @param string $table - table name
     * @param array  $map   - associative array of which values should be assigned to each field
     * @return int
     *
     * @throws MustBeOneDimensionalArray
     *
     * @psalm-param array<string, scalar|EasyPlaceholder|null> $map
     *
     * @psalm-taint-source input $table
     */
    public function insertIgnore(string $table, array $map): int
    {
        if (!empty($map)) {
            if (!$this->is1DArray($map)) {
                throw new MustBeOneDimensionalArray(
                    'Only one-dimensional arrays are allowed.'
                );
            }
        }

        list($queryString, $values) = $this->buildInsertQueryBoolSafe(
            $table,
            $map,
            false
        );

        return (int) $this->safeQuery(
            $queryString,
            $values,
            PDO::FETCH_BOTH,
            true
        );
    }

    /**
     * Insert a row into the table, ignoring on key collisions
     *
     * @param string $table - table name
     * @param array  $map   - associative array of which values should be assigned to each field
     * @param array $on_duplicate_key_update
     * @return int
     *
     * @throws MustBeOneDimensionalArray
     *
     * @psalm-param array<string, scalar|EasyPlaceholder|null> $map
     * @psalm-param array<int, string> $on_duplicate_key_update
     *
     * @psalm-taint-source input $table
     */
    public function insertOnDuplicateKeyUpdate(
        string $table,
        array $map,
        array $on_duplicate_key_update
    ): int {
        if (!empty($map)) {
            if (!$this->is1DArray($map)) {
                throw new MustBeOneDimensionalArray(
                    'Only one-dimensional arrays are allowed.'
                );
            }
        }

        list($queryString, $values) = $this->buildInsertQueryBoolSafe(
            $table,
            $map,
            $on_duplicate_key_update
        );

        return (int) $this->safeQuery(
            (string) $queryString,
            $values,
            PDO::FETCH_BOTH,
            true
        );
    }

    /**
     * Insert a new record then get a particular field from the new row
     *
     * @param          string $table
     * @param          array  $map
     * @param          string $field
     * @return         ?scalar
     *
     * @throws InvalidArgumentException
     *
     * @psalm-param array<string, scalar|null> $map
     *
     * @psalm-taint-source input $table
     * @psalm-taint-source input $field
     */
    public function insertGet(
        string $table,
        array $map,
        string $field
    ): string|int|float|bool|null {
        if (empty($map)) {
            throw new InvalidArgumentException('An empty array is not allowed for insertGet()');
        }
        if ($this->insert($table, $map) < 1) {
            throw new QueryError('Insert failed');
        }
        $post = [];
        $params = [];
        /**
         * @var string $i
         * @var string|bool|null|int|float $v
         */
        foreach ($map as $i => $v) {
            // Escape the identifier to prevent stupidity
            /** @psalm-taint-escape sql */
            $i = $this->escapeIdentifier($i);
            if ($v === null) {
                $post []= " {$i} IS NULL ";
            } elseif (is_bool($v)) {
                $post []= $this->makeBooleanArgument($i, $v);
            } else {
                // We use prepared statements for handling the users' data
                $post []= " {$i} = ? ";
                $params[] = $v;
            }
        }
        $conditions = implode(' AND ', $post);
        // We want the latest value:
        $limiter = match ($this->dbEngine) {
            'mysql' => ' ORDER BY ' .
                $this->escapeIdentifier($field) .
                ' DESC LIMIT 0, 1 ',
            'pgsql' => ' ORDER BY ' .
                $this->escapeIdentifier($field) .
                ' DESC OFFSET 0 LIMIT 1 ',
            default => '',
        };
        /** @psalm-taint-escape sql */
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
     * @param  string $table - table name
     * @param  array  $maps  - array of associative array specifying values
     *                                 should be assigned to each field
     * @return int
     *
     * @throws InvalidArgumentException
     * @throws MustBeOneDimensionalArray
     * @throws QueryError
     *
     * @psalm-taint-source input $table
     */
    public function insertMany(string $table, array $maps): int
    {
        if (count($maps) < 1) {
            throw new InvalidArgumentException(
                'Argument 2 passed to ' .
                    static::class .
                '::' .
                    __METHOD__ .
                '() must contain at least one field set!'
            );
        }

        $mapsKeys = array_keys($maps);
        /** @var array-key $firstKey */
        $firstKey = array_shift($mapsKeys);
        /**
         * @var array $first
         */
        $first = $maps[$firstKey];

        /**
         * @var array $map
         */
        foreach ($maps as $map) {
            if (!$this->is1DArray($map)) {
                throw new MustBeOneDimensionalArray(
                    'Every map in the second argument should have the same number of columns.'
                );
            }
        }

        $queryString = $this->buildInsertQuery($table, array_keys($first));

        // Now let's run a query with the parameters
        $stmt = $this->prepare($queryString);
        $count = 0;

        /**
         * @var array $params
         */
        foreach ($maps as $params) {
            $stmt->execute(array_values($params));
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
     *
     * @throws EasyDBException
     * @throws QueryError
     *
     * @psalm-param array<string, scalar|null> $map
     *
     * @psalm-taint-source input $table
     */
    public function insertReturnId(string $table, array $map, string $sequenceName = ''): string
    {
        if ($this->dbEngine === 'pgsql') {
            throw new EasyDBException(
                'Do not use insertReturnId() with PostgreSQL. Use insertGet() instead, ' .
                'with an explicit column name rather than a sequence name.'
            );
        }
        if (!$this->insert($table, $map)) {
            throw new QueryError('Could not insert a new row into ' . $table . '.');
        }
        if ($sequenceName) {
            return $this->lastInsertId($sequenceName);
        }
        return $this->lastInsertId();
    }

    /**
     * Get a query string for an INSERT statement.
     *
     * @param string $table
     * @param array  $columns list of columns that will be inserted
     * @return string
     *
     * @throws MustBeOneDimensionalArray
     *   If $columns is not a one-dimensional array.
     *
     * @psalm-taint-source input $table
     */
    public function buildInsertQuery(string $table, array $columns): string
    {
        if (!empty($columns)) {
            if (!$this->is1DArray($columns)) {
                throw new MustBeOneDimensionalArray(
                    'Only one-dimensional arrays are allowed.'
                );
            }
        }

        $columns = array_map([$this, 'escapeIdentifier'], $columns);
        $placeholders = array_fill(0, count($columns), '?');

        return sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->escapeIdentifier($table),
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
    }

    /**
     * Get an query string for an INSERT statement.
     *
     * @template T as array<string, scalar|null>
     *
     * @param string $table
     * @param array  $map
     * @param bool|array<int, string>|null $duplicates_mode - null for straight-forward insert,
     *                                                         false for ignore,
     *                                                         array for on-duplicate-key-update
     * @return array {0: string, 1: array}
     *
     * @throws MustBeOneDimensionalArray
     *   If $columns is not a one-dimensional array.
     *
     * @psalm-param array<string, scalar|EasyPlaceholder|null> $map
     * @psalm-param null|false|array<int, string> $duplicates_mode
     * @psalm-return array{0:string, 1:array<int, scalar>}
     *
     */
    public function buildInsertQueryBoolSafe(
        string $table,
        array $map,
        array|bool|null $duplicates_mode = null
    ): array {
        /** @var array<int, string> $columns */
        $columns = [];
        /** @var array<int, string> $placeholders */
        $placeholders = [];
        $values = [];
        /**
         * @var string $key
         * @var scalar|EasyPlaceholder|null $value
         */
        foreach ($map as $key => $value) {
            $columns[] = $key;
            if (is_null($value)) {
                $placeholders[] = 'NULL';
            } elseif (is_bool($value)) {
                if ($this->dbEngine === 'sqlite') {
                    $placeholders[] = $value ? "'1'" : "'0'";
                } else {
                    $placeholders[] = $value ? 'TRUE' : 'FALSE';
                }
            } elseif ($value instanceof EasyPlaceholder) {
                $placeholders[] = $value->mask();
                $values = array_merge($values, $value->values());
            } else {
                $placeholders[] = '?';
                $values[] = $value;
            }
        }
        $columns = array_map([$this, 'escapeIdentifier'], $columns);

        /**
        * @var array<int, string>
        */
        $duplicates_updates = [];

        if (is_array($duplicates_mode)) {
            foreach ($duplicates_mode as $column_name) {
                $escaped_column_name = $this->escapeIdentifier($column_name);

                $duplicates_updates[] =
                    $escaped_column_name .
                    ' = VALUES(' .
                    $escaped_column_name .
                    ')';
            }
        }

        $query = sprintf(
            'INSERT%sINTO %s (%s) VALUES (%s)%s',
            (false === $duplicates_mode ? ' IGNORE ' : ' '),
            $this->escapeIdentifier($table),
            implode(', ', $columns),
            implode(', ', $placeholders),
            (
                (count($duplicates_updates) > 0)
                    ? (
                        ' ON DUPLICATE KEY UPDATE ' .
                        implode(', ', $duplicates_updates)
                    )
                    : ''
            )
        );

        /**
         * @psalm-var array{0:string, 1:array<int, scalar>}
         */
        return array($query, $values);
    }

    /**
     * Variadic shorthand for $this->safeQuery()
     *
     * @param  string $statement SQL query without user data
     * @param  mixed  ...$params Parameters
     * @return mixed
     *
     * @throws TypeError
     *
     * @psalm-taint-sink sql $statement
     */
    public function q(string $statement, ...$params): array
    {
        $result = $this->safeQuery(
            $statement,
            $params,
            self::DEFAULT_FETCH_STYLE,
            false,
            true
        );
        if (!is_array($result)) {
            throw new TypeError('Return value must be an array');
        }
        return $result;
    }

    /**
     * Similar to $this->q() except it only returns a single row
     *
     * @param  string $statement SQL query without user data
     * @param  string|int|float|bool|null  ...$params Parameters
     * @return array
     *
     * @throws TypeError
     *
     * @psalm-taint-sink sql $statement
     */
    public function row(string $statement, ...$params): array
    {
        /**
         * @var array|int $result
         */
        $result = $this->safeQuery(
            $statement,
            $params,
            self::DEFAULT_FETCH_STYLE,
            false,
            true
        );
        if (is_array($result)) {
            $first = array_shift($result);
            if (!is_array($first)) {
                /* Do not TypeError on empty results */
                return [];
            }
            return $first;
        }
        return [];
    }

    /**
     * Variadic shorthand for $this->safeQuery()
     *
     * @param  string $statement SQL query without user data
     * @param  scalar|null  ...$params Parameters
     * @return array
     *
     * @psalm-taint-sink sql $statement
     */
    public function run(string $statement, ...$params): array
    {
        $results = $this->safeQuery(
            $statement,
            $params,
            self::DEFAULT_FETCH_STYLE,
            false,
            true
        );
        if (!is_array($results)) {
            return [];
        }
        return $results;
    }

    /**
     * Perform a Parametrized Query
     *
     * @param  string $statement         The query string (hopefully untainted
     *                                   by user input)
     * @param  array  $params            The parameters (used in prepared
     *                                   statements)
     * @param  int    $fetchStyle        PDO::FETCH_STYLE
     * @param  bool   $returnNumAffected Return the number of rows affected?
     * @param  bool   $calledWithVariadicParams Indicates method is being invoked from variadic $params method
     * @return array|int|object
     *
     * @throws InvalidArgumentException
     * @throws MustBeOneDimensionalArray
     * @throws QueryError
     * @throws TypeError
     *
     * @psalm-taint-sink sql $statement
     */
    public function safeQuery(
        string $statement,
        array $params = [],
        int $fetchStyle = self::DEFAULT_FETCH_STYLE,
        bool $returnNumAffected = false,
        bool $calledWithVariadicParams = false
    ): array|int|object {
        if ($fetchStyle === self::DEFAULT_FETCH_STYLE) {
            if (isset($this->options[PDO::ATTR_DEFAULT_FETCH_MODE])) {
                /**
                 * @var int $fetchStyle
                 */
                $fetchStyle = $this->options[PDO::ATTR_DEFAULT_FETCH_MODE];
            } else {
                $fetchStyle = PDO::FETCH_ASSOC;
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
                throw new MustBeOneDimensionalArray(
                    'Only one-dimensional arrays are allowed, please use ' .
                    __METHOD__ .
                    '()'
                );
            }

            throw new MustBeOneDimensionalArray(
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
     * @return string|int|float|bool|null
     *
     * @throws MustBeOneDimensionalArray
     */
    public function single(string $statement, array $params = []): string|int|float|bool|null
    {
        if (!$this->is1DArray($params)) {
            throw new MustBeOneDimensionalArray(
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
     *                             assigned to each field
     * @param array|EasyStatement $conditions WHERE clause
     * @return int
     *
     * @throws QueryError
     * @throws InvalidTableName
     *
     * @psalm-taint-source input $table
     */
    public function update(
        string $table,
        array $changes,
        EasyStatement|array $conditions
    ): int {
        if (empty($table)) {
            throw new InvalidTableName(
                'Table name must be a non-empty string.'
            );
        }
        if ($conditions instanceof EasyStatement) {
            return $this->updateWhereStatement($table, $changes, $conditions);
        }
        return $this->updateWhereArray($table, $changes, $conditions);
    }

    /**
     * Update a row in a database table.
     *
     * @param  string $table      Table name
     * @param  array  $changes    Associative array of which values should be
     *                            assigned to each field
     * @param  array  $conditions WHERE clause
     * @return int
     *
     * @psalm-taint-source input $table
     */
    protected function updateWhereArray(string $table, array $changes, array $conditions): int
    {
        if (empty($changes) || empty($conditions)) {
            return 0;
        }
        if (!$this->is1DArray($changes) || !$this->is1DArray($conditions)) {
            throw new MustBeOneDimensionalArray(
                'Only one-dimensional arrays are allowed.'
            );
        }
        /** @psalm-taint-escape sql */
        $queryString = 'UPDATE ' . $this->escapeIdentifier($table) . ' SET ';
        $params = [];

        // The first set (pre WHERE)
        $pre = [];
        /**
         * @var string $i
         * @var string|int|bool|float|EasyPlaceholder|null $v
         */
        foreach ($changes as $i => $v) {
            /** @psalm-taint-escape sql */
            $i = $this->escapeIdentifier($i);
            if ($v === null) {
                $pre []= " {$i} = NULL";
            } elseif (is_bool($v)) {
                $pre []= $this->makeBooleanArgument($i, $v);
            } elseif ($v instanceof EasyPlaceholder) {
                $pre []= " {$i} = ".$v->mask();
                $params = array_merge($params, $v->values());
            } else {
                $pre []= " {$i} = ?";
                $params[] = $v;
            }
        }
        $queryString .= implode(', ', $pre);
        $queryString .= " WHERE ";

        // The last set (post WHERE)
        $post = [];
        /**
         * @var string $i
         * @var string|int|bool|float|null $v
         */
        foreach ($conditions as $i => $v) {
            /** @psalm-taint-escape sql */
            $i = $this->escapeIdentifier($i);
            if ($v === null) {
                $post []= " {$i} IS NULL";
            } elseif (is_bool($v)) {
                $post []= $this->makeBooleanArgument($i, $v);
            } else {
                $post []= " {$i} = ? ";
                $params[] = $v;
            }
        }
        $queryString .= implode(' AND ', $post);

        return (int) $this->safeQuery(
            $queryString,
            $params,
            PDO::FETCH_BOTH,
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
     *
     * @psalm-taint-source input $table
     */
    protected function updateWhereStatement(
        string $table,
        array $changes,
        EasyStatement $conditions
    ): int {
        if (empty($changes) || $conditions->count() < 1) {
            return 0;
        }
        /** @psalm-taint-escape sql */
        $queryString = 'UPDATE ' . $this->escapeIdentifier($table) . ' SET ';
        $params = [];

        // The first set (pre WHERE)
        $pre = [];
        /**
         * @var string $i
         * @var string|int|bool|float|EasyPlaceholder|null $v
         */
        foreach ($changes as $i => $v) {
            /** @psalm-taint-escape sql */
            $i = $this->escapeIdentifier($i);
            if ($v === null) {
                $pre []= " {$i} = NULL";
            } elseif (is_bool($v)) {
                $pre []= $this->makeBooleanArgument($i, $v);
            } elseif ($v instanceof EasyPlaceholder) {
                $pre []= " {$i} = ".$v->mask();
                $params = array_merge($params, $v->values());
            } else {
                $pre []= " {$i} = ?";
                $params[] = $v;
            }
        }

        $queryString .= implode(', ', $pre);
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
            PDO::FETCH_BOTH,
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
            count($params) === count($params, COUNT_RECURSIVE) &&
            count(array_filter($params, 'is_array')) < 1
        );
    }

    /**
     * Try to execute a callback within the scope of a flat transaction
     * If already inside a transaction, does not start a new one.
     * Callable should accept one parameter, i.e. function (EasyDB $db) {}
     *
     * @template T
     *
     * @param callable $callback
     *
     * @psalm-param callable(EasyDB):T $callback
     *
     * @return string|int|bool|array|object|float|null
     *
     * @psalm-return T
     *
     * @throws Throwable
     */
    public function tryFlatTransaction(callable $callback): string|int|bool|array|null|object|float
    {
        $autoStartTransaction = $this->inTransaction() === false;

        // If we're starting a transaction, we don't need to catch here
        if ($autoStartTransaction) {
            $this->beginTransaction();
        }
        try {
            /**
             * @var scalar|array|object|resource|null $out
             *
             * @psalm-var T
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
     * Get the specific type of the given variable.
     *
     * @param mixed|null $v
     * @return string
     */
    protected function getValueType(mixed $v = null): string
    {
        if (is_scalar($v) || is_array($v)) {
            return (string) gettype($v);
        }
        if (is_object($v)) {
            return 'an instance of ' . get_class($v);
        }
        return (string) var_export($v, true);
    }

    /**
     * Helper for PDOStatement::fetchAll() that always returns an array or object.
     *
     * @param  PDOStatement $stmt
     * @param  int           $fetchStyle
     * @return array|object
     *
     * @throws TypeError
     */
    protected function getResultsStrictTyped(
        PDOStatement $stmt,
        int $fetchStyle = PDO::FETCH_ASSOC
    ): object|array {
        /**
         * @var array|object|bool $results
         */
        $results = $stmt->fetchAll($fetchStyle);
        if (is_array($results)) {
            return $results;
        } elseif (is_object($results)) {
            return $results;
        }
        throw new TypeError('Unexpected return type: ' . $this->getValueType($results));
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
    public function errorCode(): mixed
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
    public function getAttribute(...$args): mixed
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
     * @psalm-taint-sink sql $args[0]
     *
     * @param  mixed ...$args
     * @return PDOStatement
     *
     * @throws QueryError
     */
    public function prepare(mixed ...$args): PDOStatement
    {
        $trimmed = trim($args[0]);
        if (empty($trimmed)) {
            throw new QueryError(
                "Empty query passed to prepare()"
            );
        }
        return $this->pdo->prepare(...$args);
    }

    /**
     * Executes an SQL statement, returning a result set as a PDOStatement object
     *
     * @param  string ...$args
     * @return PDOStatement
     */
    public function query(...$args): PDOStatement
    {
        return $this->pdo->query(...$args);
    }

    /**
     * Quotes a string for use in a query
     *
     * @param  string ...$args
     * @return string
     * @psalm-suppress InvalidArgument
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
     * @param  string|bool|int|float $value
     * @return bool
     *
     * @throws EasyDBException
     */
    public function setAttribute(int $attr, string|bool|int|float $value): bool
    {
        if ($attr === PDO::ATTR_EMULATE_PREPARES) {
            if ($value !== false) {
                throw new EasyDBException(
                    'EasyDB does not allow the use of emulated prepared statements, ' .
                    'which would be a security downgrade.'
                );
            }
        }
        if ($attr === PDO::ATTR_ERRMODE) {
            if ($value !== PDO::ERRMODE_EXCEPTION) {
                throw new EasyDBException(
                    'EasyDB only allows the safest-by-default error mode (exceptions).'
                );
            }
        }
        return $this->pdo->setAttribute($attr, $value);
    }
}
