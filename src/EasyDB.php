<?php
declare (strict_types=1);

namespace ParagonIE\EasyDB;

use \ParagonIE\EasyDB\Exception as Issues;

/**
 * Class EasyDB
 * @package ParagonIE\EasyDB
 */
class EasyDB
{
    protected $dbengine = null;
    protected $pdo = null;

    /**
     * Dependency-Injectable constructor
     *
     * @param \PDO $pdo
     * @param string $dbengine
     */
    public function __construct(\PDO $pdo, string $dbengine = '')
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
        $this->dbengine = $dbengine;
    }
    /**
     * Variadic version of $this->column()
     *
     * @param string $statement SQL query without user data
     * @param int $offset - How many columns from the left are we grabbing from each row?
     * @param mixed ...$params Parameters
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
     * @param array $params Parameters
     * @param int $offset - How many columns from the left are we grabbing from each row?
     * @return mixed
     */
    public function column(string $statement, array $params = [], int $offset = 0)
    {
        $stmt = $this->pdo->prepare($statement);
        if (!$this->is1DArray($params)) {
            throw new \InvalidArgumentException("Invalid params");
        }
        $exec = $stmt->execute($params);
        if ($exec) {
            return $stmt->fetchAll(
                \PDO::FETCH_COLUMN,
                $offset
            );
        }
        return false;
    }

    /**
     * Variadic version of $this->single()
     *
     * @param string $statement SQL query without user data
     * @params mixed ...$params Parameters
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
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function delete(string $table, array $conditions)
    {
        if (empty($table)) {
            throw new \InvalidArgumentException("Table name must be a string");
        }
        if (empty($conditions)) {
            // Don't allow foot-bullets
            return null;
        }
        if (!$this->is1DArray($conditions)){
            throw new \InvalidArgumentException("Only one-dimensional arrays are allowed");
        }
        $queryString = "DELETE FROM ".$this->escapeIdentifier($table)." WHERE ";

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
            } elseif (\is_array($v)) {
                throw new \InvalidArgumentException("Only one dimensional arrays are allowed");
            } else {
                $arr []= " {$i} = ? ";
                $params[] = $v;
            }
        }
        $queryString .= \implode(' AND ', $arr);

        return $this->safeQuery($queryString, $params);
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
    public function escapeIdentifier(string $string, $quote = true) : string
    {
        if (empty($string)) {
            throw new Issues\InvalidIdentifier("Invalid identifier: Must be a non-empty string.");
        }
        $str = \preg_replace('/[^0-9a-zA-Z_]/', '', $string);

        // The first character cannot be [0-9]:
        if (\preg_match('/^[0-9]/', $str)) {
            throw new Issues\InvalidIdentifier("Invalid identifier: Must begin with a letter or undescore.");
        }

        if ($quote) {
            switch ($this->dbengine) {
                case 'mssql':
                    return '['.$str.']';
                case 'mysql':
                    return '`'.$str.'`';
                default:
                    return '"'.$str.'"';
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
    public function escapeValueSet(array $values, string $type = 'string') : string
    {
        if (empty($values)) {
            // Default value: a subquery that will return an empty set
            return '(SELECT 1 WHERE FALSE)';
        }
        // No arrays of arrays, please
        if (!$this->is1DArray($values)) {
            throw new \InvalidArgumentException("Only one-dimensional arrays are allowed");
        }
        // Build our array
        $join = [];
        foreach ($values as $v) {
            switch ($type) {
                case 'int':
                    if (!\is_int($v)) {
                        throw new \InvalidArgumentException($v . ' is not an integer');
                    }
                    $join[] = (int) $v + 0;
                    break;
                case 'float':
                case 'decimal':
                case 'number':
                case 'numeric':
                    if (!\is_numeric($v)) {
                        throw new \InvalidArgumentException($v . ' is not a number');
                    }
                    $join[] = (float) $v + 0.0;
                    break;
                case 'string':
                    if (\is_numeric($v)) {
                        $v = (string) $v;
                    }
                    if (!\is_string($v)) {
                        throw new \InvalidArgumentException($v . ' is not a string');
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
    public function getDriver() : string
    {
        return $this->dbengine;
    }

    /**
     * Return the PDO object directly
     *
     * @return \PDO
     */
    public function getPdo() : \PDO
    {
        return $this->pdo;
    }

    /**
     * Insert a new row to a table in a database.
     *
     * @param string $table - table name
     * @param array $map - associative array of which values should be assigned to each field
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function insert(string $table, array $map)
    {
        if (empty($map)) {
            return null;
        }
        if (!$this->is1DArray($map)){
            throw new \InvalidArgumentException("Only one-dimensional arrays are allowed");
        }
        // Begin query string
        $queryString = "INSERT INTO ".$this->escapeIdentifier($table)." (";
        $phold = [];
        $_keys = [];
        $params = [];
        foreach ($map as $k => $v) {
            if ($v !== null) {
                $_keys[] = $k;
                if ($v === true) {
                    $phold[] = 'TRUE';
                } elseif ($v === false) {
                    $phold[] = 'FALSE';
                } elseif (\is_array($v)) {
                    throw new \InvalidArgumentException("Only one dimensional arrays are allowed");
                } else {
                    // When all else fails, use prepared statements:
                    $phold[] = '?';
                    $params[] = $v;
                }
            }
        }
        // Let's make sure our keys are escaped.
        $keys = [];
        foreach ($_keys as $i => $v) {
            $keys[] = $this->escapeIdentifier($v);
        }
        // Now let's append a list of our columns.
        $queryString .= \implode(', ', $keys);
        // This is the middle piece.
        $queryString .= ") VALUES (";
        // Now let's concatenate the ? placeholders
        $queryString .= \implode(', ', $phold);
        // Necessary to close the open ( above
        $queryString .= ");";
        // Now let's run a query with the parameters
        return $this->safeQuery($queryString, $params, \PDO::FETCH_ASSOC, true);
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
        if ($this->insert($table, $map)) {
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
                } elseif (\is_array($v)) {
                    throw new \InvalidArgumentException("Only one-dimensional arrays are allowed");
                } else {
                    // We use prepared statements for handling the users' data
                    $post []= " {$i} = ? ";
                    $params[] = $v;
                }
            }
            $conditions = \implode(' AND ', $post);
            // We want the latest value:
            switch ($this->dbengine) {
                case 'mysql':
                    $limiter = ' ORDER BY '.
                        $this->escapeIdentifier($field).
                        ' DESC LIMIT 0, 1 ';
                    break;
                case 'pgsql':
                    $limiter = ' ORDER BY '.
                        $this->escapeIdentifier($field).
                        ' DESC OFFSET 0 LIMIT 1 ';
                    break;
                default:
                    $limiter = '';
            }
            $query = 'SELECT '.
                $this->escapeIdentifier($field).
                ' FROM '.
                $this->escapeIdentifier($table).
                ' WHERE ' . $conditions . $limiter;
            return $this->single($query, $params);
        } else {
            throw new \Exception("Insert failed");
        }
    }

    /**
     * Insert many new rows to a table in a database. using the same prepared statement
     *
     * @param string $table - table name
     * @param array $maps - array of associative array specifying values should be assigned to each field
     * @return bool
     * @throws \InvalidArgumentException
     * @throws Issues\QueryError
     */
    public function insertMany(string $table, array $maps) : bool
    {
        if (empty($maps)) {
            return false;
        }
        $first = $maps[0];
        foreach ($maps as $map) {
            if (\count($map) < 1 || \count($map) !== \count($first)) {
                throw new \InvalidArgumentException('Every map in the second argument should have the same number of columns');
            }
        }

        // Begin query string
        $queryString = "INSERT INTO ".$this->escapeIdentifier($table)." (";

        // Let's make sure our keys are escaped.
        $keys = \array_keys($first);
        foreach ($keys as $i => $v) {
            $keys[$i] = $this->escapeIdentifier($v);
        }

        // Now let's append a list of our columns.
        $queryString .= \implode(', ', $keys);

        // This is the middle piece.
        $queryString .= ") VALUES (";

        // Now let's concatenate the ? placeholders
        $queryString .= \implode(
            ', ',
            \array_fill(0, \count($first), '?')
        );

        // Necessary to close the open ( above
        $queryString .= ");";

        // Now let's run a query with the parameters
        $exec = false;
        $stmt = $this->pdo->prepare($queryString);
        foreach ($maps as $params) {
            if (!$this->is1DArray($params)) {
                throw new \InvalidArgumentException("Only one-dimensional arrays are allowed");
            }
            $exec = $stmt->execute($params);
            // Someone could turn PDO Exceptions off, so let's check this:
            if ($exec === false) {
                throw new Issues\QueryError(json_encode([$queryString, $params, $this->pdo->errorInfo()]));
            }
        }
        return $exec;
    }

    /**
     * PHP 5.6 variadic shorthand for $this->safeQuery()
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
     * PHP 5.6 variadic shorthand for $this->safeQuery()
     *
     * @param string $statement SQL query without user data
     * @params mixed ...$params Parameters
     * @return mixed - If successful, a 2D array
     */
    public function run(string $statement, ...$params)
    {
        return $this->safeQuery($statement, $params);
    }

    /**
     * Perform a Parameterized Query
     *
     * @param string $statement
     * @param array $params
     * @param int $fetch_style
     * @param bool $returnExec
     * @return mixed -- array if SELECT
     * @throws \InvalidArgumentException
     * @throws Issues\QueryError
     */
    public function safeQuery(string $statement, array $params = [], int $fetch_style = \PDO::FETCH_ASSOC, bool $returnExec=false)
    {
        if (empty($params)) {
            $stmt = $this->pdo->query($statement);
            if ($stmt !== false) {
                return $stmt->fetchAll($fetch_style);
            }
            return false;
        }
        if (!$this->is1DArray($params)) {
            throw new \InvalidArgumentException("Only one-dimensional arrays are allowed");
        }
        $stmt = $this->pdo->prepare($statement);
        $exec = $stmt->execute($params);
        // Someone could turn PDO Exceptions off, so let's check this:
        if ($exec === false) {
            throw new Issues\QueryError(
                \json_encode([
                    $stmt,
                    $params,
                    $this->pdo->errorInfo()
                ])
            );
        }
        if ($returnExec) {
            return $returnExec;
        }
        return $stmt->fetchAll($fetch_style);
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
            throw new \InvalidArgumentException("Only one-dimensional arrays are allowed");
        }
        $stmt = $this->pdo->prepare($statement);
        $exec = $stmt->execute($params);
        // Someone could turn PDO Exceptions off, so let's check this:
        if ($exec === false) {
            throw new Issues\QueryError(
                \json_encode([
                    $stmt,
                    $params,
                    $this->pdo->errorInfo()
                ])
            );
        }
        return $stmt->fetchColumn(0);
    }

    /**
     * Update a row in a database table.
     *
     * @param string $table - table name
     * @param array $changes - associative array of which values should be assigned to each field
     * @param array $conditions - WHERE clause
     * @return mixed
     * @throws \InvalidArgumentException
     * @throws Issues\QueryError
     */
    public function update(string $table, array $changes, array $conditions)
    {
        if (empty($changes) || empty($conditions)) {
            return null;
        }
        if (!$this->is1DArray($changes) || !$this->is1DArray($conditions)) {
            throw new \InvalidArgumentException("Only one-dimensional arrays are allowed");
        }
        $queryString = "UPDATE ".$this->escapeIdentifier($table)." SET ";
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

        return $this->safeQuery($queryString, $params);
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
     */
    public function beginTransaction(...$args) : bool
    {
        return $this->pdo->beginTransaction(...$args);
    }
    /**
     * Commits a transaction
     */
    public function commit(...$args) : bool
    {
        return $this->pdo->commit(...$args);
    }
    /**
     * Fetch the SQLSTATE associated with the last operation on the database
     * handle
     */
    public function errorCode(...$args)
    {
        return $this->pdo->errorCode(...$args);
    }
    /**
     * Fetch extended error information associated with the last operation on
     * the database handle
     */
    public function errorInfo(...$args) : array
    {
        return $this->pdo->errorInfo(...$args);
    }
    /**
     * Execute an SQL statement and return the number of affected rows
     */
    public function exec(...$args) : int
    {
        return $this->pdo->exec(...$args);
    }
    /**
     * Retrieve a database connection attribute
     */
    public function getAttribute(...$args)
    {
        return $this->pdo->getAttribute(...$args);
    }
    /**
     * Return an array of available PDO drivers
     */
    public function getAvailableDrivers(...$args) : array
    {
        return $this->pdo->getAvailableDrivers(...$args);
    }
    /**
     * Checks if inside a transaction
     */
    public function inTransaction(...$args) : bool
    {
        return $this->pdo->inTransaction(...$args);
    }
    /**
     * Returns the ID of the last inserted row or sequence value
     */
    public function lastInsertId(...$args) : string
    {
        return $this->pdo->lastInsertId(...$args);
    }
    /**
     * Prepares a statement for execution and returns a statement object
     */
    public function prepare(...$args) : \PDOStatement
    {
        return $this->pdo->prepare(...$args);
    }
    /**
     * Executes an SQL statement, returning a result set as a PDOStatement object
     */
    public function query(...$args) : \PDOStatement
    {
        return $this->pdo->query(...$args);
    }
    /**
     * Quotes a string for use in a query
     */
    public function quote(...$args) : string
    {
        return $this->pdo->quote(...$args);
    }
    /**
     * Rolls back a transaction
     */
    public function rollBack(...$args) : bool
    {
        return $this->pdo->rollBack(...$args);
    }
    /**
     * Set an attribute
     */
    public function setAttribute(...$args) : bool
    {
        return $this->pdo->setAttribute(...$args);
    }

    /**
     * Make sure none of this array's elements are arrays
     *
     * @param array $params
     * @return bool
     */
    public function is1DArray(array $params) : bool
    {
        return (
            \count($params) === \count($params, COUNT_RECURSIVE) &&
            \count(\array_filter($params, 'is_array')) < 1
        );
    }
}
