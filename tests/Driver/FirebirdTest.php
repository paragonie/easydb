<?php
declare(strict_types=1);
namespace ParagonIE\EasyDB\Tests\Driver;

use ParagonIE\EasyDB\EasyDB;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(EasyDB::class)]
class FirebirdTest extends DriverTestCase
{
    protected function getDsn(): string
    {
        $host = \getenv('FIREBIRD_HOST') ?: '127.0.0.1';
        $db = \getenv('FIREBIRD_DB') ?: '/var/lib/firebird/3.0/data/easydb.fdb';
        return "firebird:dbname={$host}:{$db}";
    }

    protected function getUsername(): string
    {
        return \getenv('FIREBIRD_USER') ?: 'SYSDBA';
    }

    protected function getPassword(): string
    {
        return \getenv('FIREBIRD_PASS') ?: 'masterkey';
    }
}
