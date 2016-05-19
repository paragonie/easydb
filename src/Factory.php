<?php
namespace ParagonIE\EasyDB;

use \ParagonIE\EasyDB\Exception as Issues;

/**
 * Class Factory
 *
 * @package ParagonIE\EasyDB
 */
abstract class Factory
{
    /**
     * Create a new EasyDB object based on PDO constructors
     * 
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array $options
     * @return \ParagonIE\EasyDB\EasyDB
     * @throws Issues\ConstructorFailed
     */
    public static function create($dsn, $username = null, $password = null, $options = [])
    {
        $dbengine = null;
        $post_query = null;

        // Let's grab the DB engine
        if (strpos($dsn, ':') !== false) {
            $dbengine = explode(':', $dsn)[0];
        }

        // If no charset is specified, default to UTF-8
        switch ($dbengine) {
            case 'mysql':
                if (strpos($dsn, ';charset=') === false) {
                    $dsn .= ';charset=utf8';
                }
                break;
            case 'pgsql':
                $post_query = 'SET NAMES UNICODE';
                break;
        }
        
        try {
            $pdo = new \PDO($dsn, $username, $password, $options);
        } catch (\PDOException $e) {
            throw new Issues\ConstructorFailed(
                'Could not create a PDO connection. Please check your username and password.'
            );
        }

        // Let's turn off emulated prepares
        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

        if (!empty($post_query)) {
            $pdo->query($post_query);
        }
        
        return new EasyDB($pdo, $dbengine);
    }
}
