<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB;

use ParagonIE\EasyDB\Exception\{
    ConstructorFailed
};
use PDO;
use PDOException;
use function
    explode,
    is_string,
    str_contains;

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
     * @param ?string $username
     * @param ?string $password
     * @param array $options
     * @return EasyDB
     *
     * @throws ConstructorFailed
     *
     * @psalm-taint-sink user_secret $password
     */
    public static function create(
        string $dsn,
        string $username = null,
        string $password = null,
        array $options = []
    ): EasyDB {
        return static::fromArray([$dsn, $username, $password, $options]);
    }
    
    /**
     * Create a new EasyDB object from array of parameters
     *
     * @param array $config
     * @return EasyDB
     *
     * @throws ConstructorFailed
     */
    public static function fromArray(array $config): EasyDB
    {
        /** @var string $dsn */
        $dsn      = $config[0];
        /** @var string|null $username */
        $username = $config[1] ?? null;
        /** @var string|null $password */
        $password = $config[2] ?? null;
        /** @var array $options */
        $options  = $config[3] ?? [];

        $dbEngine = '';
        $post_query = null;

        if (!is_string($username)) {
            $username = '';
        }
        if (!is_string($password)) {
            $password = '';
        }

        // Let's grab the DB engine
        if (str_contains($dsn, ':')) {
            $dbEngine = explode(':', $dsn)[0];
        }

        $post_query = '';

        // If no charset is specified, default to UTF-8
        switch ($dbEngine) {
            case 'mysql':
                if (!str_contains($dsn, ';charset=')) {
                    $dsn .= ';charset=utf8mb4';
                }
                break;
            case 'pgsql':
                $post_query = "SET NAMES 'UNICODE'";
                break;
        }

        try {
            $pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'could not find driver')) {
                throw (new ConstructorFailed(
                    'Could not create a PDO connection. Is the driver installed/enabled?'
                ))->setRealException($e);
            }
            
            if (str_contains($e->getMessage(), 'unknown database')) {
                throw (new ConstructorFailed(
                    'Could not create a PDO connection. Check that your database exists.'
                ))->setRealException($e);
            }
            
            // Don't leak credentials directly if we can.
            throw (new ConstructorFailed(
                'Could not create a PDO connection. Please check your username and password.'
            ))->setRealException($e);
        }

        if (!empty($post_query)) {
            $pdo->query($post_query);
        }

        return new EasyDB($pdo, $dbEngine, $options);
    }
}
