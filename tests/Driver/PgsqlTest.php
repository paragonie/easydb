<?php
declare(strict_types=1);
namespace ParagonIE\EasyDB\Tests\Driver;

use ParagonIE\EasyDB\EasyDB;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(EasyDB::class)]
class PgsqlTest extends DriverTestCase
{
    protected function getDsn(): string
    {
        $host = \getenv('PGSQL_HOST') ?: '127.0.0.1';
        $db = \getenv('PGSQL_DB') ?: 'easydb';
        return "pgsql:host={$host};dbname={$db}";
    }

   protected function getUsername(): string
    {
        return \getenv('PGSQL_USER') ?: 'postgres';
    }

    protected function getPassword(): string
    {
        return \getenv('PGSQL_PASS') ?: 'password';
    }
}
