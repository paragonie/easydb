<?php
namespace ParagonIE\EasyDB;

use \Paragonie\EasyDB\Exception as Issues;

class EasyDB
{
    protected $dbengine = null;
    protected $pdo = null;
    
    public function __construct(\PDO $pdo, $dbengine = '')
    {
        $this->pdo = $pdo;
        $this->dbengine = $driver;
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
        // This array accumulates our results
        $columns = [];
        
        $stmt = $this->pdo->prepare($statement);
        $exec = $stmt->execute($params);
        if ($exec) {
            do {
                $curr = $stmt->fetchColumn($offset);
                if ($curr === false) {
                    break;
                }
                $columns[] = $curr;
            } while($curr !== false);
            return $curr;
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
     */
    public function delete($table, array $conditions)
    {
        if (empty($conditions)) {
            // Don't allow foot-bullets
            return null;
        }
        $queryString = "DELETE FROM ".$this->escapeIdentifier($table)." WHERE ";
        
        // Simple array for joining the strings together
        $arr = [];
        foreach ($conditions as $i => $v) {
            $i = $this->escapeIdentifier($i);
            $arr []= " {$i} = ? ";
            $params[] = $v;
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
    public function escapeIdentifier($string, $quote = true)
    {
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
     */
    public function insert($table, array $map)
    {
        if (empty($map)) {
            return null;
        }

        // Begin query string
        $queryString = "INSERT INTO ".$this->escapeIdentifier($table)." (";

        // Let's make sure our keys are escaped.
        $keys = \array_keys($map);
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
            \array_fill(0, \count($map), '?')
        );

        // Necessary to close the open ( above
        $queryString .= ");";

        // Now let's run a query with the parameters
        return $this->safeQuery(
            $queryString,
            \array_values($map)
        );
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
     */
    public function safeQuery($statement, $params = [], $fetch_style = \PDO::FETCH_ASSOC)
    {
        if (empty($params)) {
            $stmt = $this->pdo->query($statement);
            if ($stmt !== false) {
                return $stmt->fetchAll($fetch_style);
            }
            return false;
        }
        $stmt = $this->pdo->prepare($statement);
        $exec = $stmt->execute($params);
        if ($exec === false) {
            throw new Issues\QueryError($statement, $params);
        }
        return $stmt->fetchAll($fetch_style);
    }

    /**
     * Fetch a single result -- useful for SELECT COUNT() queries
     *
     * @param string $statement
     * @param array $params
     * @return mixed
     */
    public function single($statement, $params = [])
    {
        $stmt = $this->pdo->prepare($statement);
        $exec = $stmt->execute($params);
        if ($exec === false) {
            throw new Issues\QueryError($statement, $params);
        }
        return $stmt->fetchColumn(0);
    }

    /**
     * Update a row in a database table.
     *
     * @param string $table - table name
     * @param array $changes - associative array of which values should be assigned to each field
     * @param array $conditions - WHERE clause
     */
    public function update($table, array $changes, array $conditions)
    {
        if (empty($changes) || empty($conditions)) {
            return null;
        }
        $queryString = "UPDATE ".$this->escapeIdentifier($table)." SET ";
        
        // The first set (pre WHERE)
        $pre = [];
        foreach ($changes as $i => $v) {
            $i = $this->escapeIdentifier($i);
            $pre []= " {$i} = ?";
            $params[] = $v;
        }
        $queryString .= \implode(', ', $pre);
        $queryString .= " WHERE ";
        
        // The last set (post WHERE)
        $post = [];
        foreach ($conditions as $i => $v) {
            $i = $this->escapeIdentifier($i);
            $post []= " {$i} = ? ";
            $params[] = $v;
        }
        $queryString .= \implode(' AND ', $post);

        return $this->safeQuery($queryString, $params);
    }
}
