<?php
declare(strict_types=1);

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
    public static function create(
        string $dsn,
        string $username = null,
        string $password = null,
        array $options = []
    ): EasyDB {
        $dbEngine = '';
        $post_query = null;

        // Let's grab the DB engine
        if (strpos($dsn, ':') !== false) {
            $dbEngine = explode(':', $dsn)[0];
        }

        // If no charset is specified, default to UTF-8
        switch ($dbEngine) {
            case 'mysql':
                if (\strpos($dsn, ';charset=') === false) {
                    $dsn .= ';charset=utf8mb4';
                }
                break;
            case 'pgsql':
                $post_query = 'SET NAMES UNICODE';
                break;
            case 'sqlite':
                $post_query = 'SET NAMES UTF8';
                break;
        }

        try {
            $pdo = new \PDO($dsn, $username, $password, $options);
        } catch (\PDOException $e) {
            // Don't leak credentials directly if we can.
            throw new Issues\ConstructorFailed(
                'Could not create a PDO connection. Please check your username and password.'
            );
        }

        // Let's turn off emulated prepares
        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

        if (!empty($post_query)) {
            $pdo->query($post_query);
        }

        return new EasyDB($pdo, $dbEngine);
    }
}
