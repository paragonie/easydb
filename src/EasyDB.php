<?php
namespace ParagonIE\EasyDB;

use \ParagonIE\EasyDB\Exception as Issues;

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
    public function __construct(\PDO $pdo, $dbengine = '')
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        $this->dbengine = $dbengine;
    }
    
    /**
     * Fetch a column
     * 
     * @param string $statement SQL query without user data
     * @param int $offset - How many columns from the left are we grabbing from each row?
     * @params ... $params Parameters
     * @return mixed
     */
    public function column($statement, $params = [], $offset = 0)
    {   
        $stmt = $this->pdo->prepare($statement);
        if (\count($params) !== \count($params, COUNT_RECURSIVE)){
            throw new \InvalidArgumentException("Invalid params");
        }
        $exec = $stmt->execute($params);
        if ($exec) {
            return $stmt->fetchAll(
                \PDO::FETCH_ASSOC,
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
    public function cell($statement, ...$params)
    {
        return $this->single($statement, $params);
    }
    
    /**
     * Delete rows in a database table.
     *
     * @param string $table - table name
     * @param array $conditions - WHERE clause
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function delete($table, array $conditions)
    {
        if (!is_string($table)) {
            throw new \InvalidArgumentException("Table name must be a string");
        }
        if (empty($conditions)) {
            // Don't allow foot-bullets
            return null;
        }
        $queryString = "DELETE FROM ".$this->escapeIdentifier($table)." WHERE ";
        
        // Simple array for joining the strings together
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
     * @throws InvalidArgumentException
     */
    public function escapeIdentifier($string, $quote = true)
    {
        if (!\is_string($string)) {
            throw new Issues\InvalidIdentifier("Invalid identifier: Must be a string.");
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
     * Which database driver are we operating on?
     * 
     * @return string
     */
    public function getDriver()
    {
        return $this->driver;
    }
    
    /**
     * Return the PDO object directly
     * 
     * @return \PDO
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     * Insert a new row to a table in a database.
     *
     * @param string $table - table name
     * @param array $map - associative array of which values should be assigned to each field
     * @throws InvalidArgumentException
     */
    public function insert($table, array $map)
    {
        if (empty($map)) {
            return null;
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
        return $this->safeQuery($queryString, $params);
    }
    
    /**
     * Insert many new rows to a table in a database. using the same prepared statement
     *
     * @param string $table - table name
     * @param array $maps - array of associative array specifying values should be assigned to each field
     * @throws InvalidArgumentException
     * @throws Issues\QueryError
     */
    public function insertMany($table, array $maps)
    {
        if (!is_string($table)) {
            throw new \InvalidArgumentException("Table name must be a string");
        }
        if (empty($maps)) {
            return null;
        }
        $first = $maps[0];
        foreach ($maps as $map) {
            if (\count($map) < 1 || \count($map) !== \count($first)) {
                throw new \InvalidArgumentException('Every map in the second argument should have the same number of columns');
            }
            if (\count($map) !== \count($map, COUNT_RECURSIVE)) {
                throw new \InvalidArgumentException('insertMany() only accepts a two-dimensional array; you attempted to pass at least three dimensions');
            }
        }

        // Begin query string
        $queryString = "INSERT INTO ".$this->escapeIdentifier($table)." (";

        // Let's make sure our keys are escaped.
        $keys = \array_keys($first);
        foreach ($keys as $i => $v) {
            if (!is_string($v)) {
                throw new \InvalidArgumentException("Column name must be a string");
            }
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
        $stmt = $this->pdo->prepare($queryString);
        foreach ($maps as $params) {
            if (\count($params) !== \count($params, COUNT_RECURSIVE)){
                throw new \InvalidArgumentException("Invalid params");
            }
            $exec = $stmt->execute($params);
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
     * @params mixed ...$params Parameters
     */
    public function q($statement, ...$params)
    {
        return $this->safeQuery($statement, $params);
    }

    /**
     * Similar to $this->q() except it only returns a single row
     *
     * @param string $statement SQL query without user data
     * @params mixed ...$params Parameters
     */
    public function row($statement, ...$params)
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
    public function run($statement, ...$params)
    {
        return $this->safeQuery($statement, $params);
    }

    /**
     * Perform a Parameterized Query
     *
     * @param string $statement
     * @param array $params
     * @param const $fetch_style
     * @return mixed -- array if SELECT
     * @throws InvalidArgumentException
     * @throws Issues\QueryError
     */
    public function safeQuery($statement, $params = [], $fetch_style = \PDO::FETCH_ASSOC)
    {
        if (!is_string($statement)) {
            throw new \InvalidArgumentException("Statement must be a string");
        }
        if (empty($params)) {
            $stmt = $this->pdo->query($statement);
            if ($stmt !== false) {
                return $stmt->fetchAll($fetch_style);
            }
            return false;
        }
        $stmt = $this->pdo->prepare($statement);
        if (\count($params) !== \count($params, COUNT_RECURSIVE)){
            throw new \InvalidArgumentException("Invalid params");
        }
        $exec = $stmt->execute($params);
        if ($exec === false) {
            throw new Issues\QueryError(
                \json_encode([
                    $stmt,
                    $params, 
                    $this->pdo->errorInfo()
                ])
            );
        }
        return $stmt->fetchAll($fetch_style);
    }

    /**
     * Fetch a single result -- useful for SELECT COUNT() queries
     *
     * @param string $statement
     * @param array $params
     * @return mixed
     * @throws InvalidArgumentException
     * @throws Issues\QueryError
     */
    public function single($statement, $params = [])
    {
        if (!is_string($statement)) {
            throw new \InvalidArgumentException("Statement must be a string");
        }
        $stmt = $this->pdo->prepare($statement);
        $exec = $stmt->execute($params);
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
     * @throws InvalidArgumentException
     * @throws Issues\QueryError
     */
    public function update($table, array $changes, array $conditions)
    {
        if (!is_string($table)) {
            throw new \InvalidArgumentException("Table name must be a string");
        }
        if (empty($changes) || empty($conditions)) {
            return null;
        }
        $queryString = "UPDATE ".$this->escapeIdentifier($table)." SET ";
        
        // The first set (pre WHERE)
        $pre = [];
        foreach ($changes as $i => $v) {
            if (!is_string($i)) {
                throw new \InvalidArgumentException("Column name must be a string");
            }
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
            if (!is_string($i)) {
                throw new \InvalidArgumentException("Column name must be a string");
            }
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
    public function beginTransaction(...$args)
    {
        return $this->pdo->beginTransaction(...$args);
    }
    /**
     * Commits a transaction
     */
    public function commit(...$args)
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
    public function errorInfo(...$args)
    {
        return $this->pdo->errorInfo(...$args);
    }
    /**
     * Execute an SQL statement and return the number of affected rows
     */
    public function exec(...$args)
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
    public function getAvailableDrivers(...$args)
    {
        return $this->pdo->getAvailableDrivers(...$args);
    }
    /**
     * Checks if inside a transaction
     */
    public function inTransaction(...$args)
    {
        return $this->pdo->inTransaction(...$args);
    }
    /**
     * Returns the ID of the last inserted row or sequence value
     */
    public function lastInsertId(...$args)
    {
        return $this->pdo->lastInsertId(...$args);
    }
    /**
     * Prepares a statement for execution and returns a statement object
     */
    public function prepare(...$args)
    {
        return $this->pdo->prepare(...$args);
    }
    /**
     * Executes an SQL statement, returning a result set as a PDOStatement object
     */
    public function query(...$args)
    {
        return $this->pdo->query(...$args);
    }
    /**
     * Quotes a string for use in a query
     */
    public function quote(...$args)
    {
        return $this->pdo->quote(...$args);
    }
    /**
     * Rolls back a transaction
     */
    public function rollBack(...$args)
    {
        return $this->pdo->rollBack(...$args);
    }
    /**
     * Set an attribute
     */
    public function setAttribute(...$args)
    {
        return $this->pdo->setAttribute(...$args);
    }
}
