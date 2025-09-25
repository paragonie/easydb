<?php
declare(strict_types=1);
namespace ParagonIE\EasyDB\Tests\Driver;

use ParagonIE\EasyDB\EasyDB;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(EasyDB::class)]
class MysqlTest extends DriverTestCase
{
    protected function getDsn(): string
    {
        $host = \getenv('MYSQL_HOST') ?: '127.0.0.1';
        $db = \getenv('MYSQL_DB') ?: 'easydb';
        return "mysql:host={$host};dbname={$db}";
    }

    protected function getUsername(): string
    {
        return \getenv('MYSQL_USER') ?: 'root';
    }

    protected function getPassword(): string
    {
        return \getenv('MYSQL_PASS') ?: '';
    }
}
