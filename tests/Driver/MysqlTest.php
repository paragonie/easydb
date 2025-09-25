<?php
declare(strict_types=1);
namespace ParagonIE\EasyDB\Tests\Driver;

class MysqlTest extends DriverTestCase
{
    /**
     * @return string
     */
    protected function getDsn(): string
    {
        $host = \getenv('MYSQL_HOST') ?: '127.0.0.1';
        $db = \getenv('MYSQL_DB') ?: 'easydb';
        return "mysql:host={$host};dbname={$db}";
    }

    /**
     * @return string
     */
    protected function getUsername(): string
    {
        return \getenv('MYSQL_USER') ?: 'root';
    }

    /**
     * @return string
     */
    protected function getPassword(): string
    {
        return \getenv('MYSQL_PASS') ?: '';
    }
}
