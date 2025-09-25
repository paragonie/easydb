<?php
declare(strict_types=1);
namespace ParagonIE\EasyDB\Tests\Driver;

class FirebirdTest extends BaseTest
{
    /**
     * @return string
     */
    protected function getDsn(): string
    {
        $host = \getenv('FIREBIRD_HOST') ?: '127.0.0.1';
        $db = \getenv('FIREBIRD_DB') ?: '/var/lib/firebird/3.0/data/easydb.fdb';
        return "firebird:dbname={$host}:{$db}";
    }

    /**
     * @return string
     */
    protected function getUsername(): string
    {
        return \getenv('FIREBIRD_USER') ?: 'SYSDBA';
    }

    /**
     * @return string
     */
    protected function getPassword(): string
    {
        return \getenv('FIREBIRD_PASS') ?: 'masterkey';
    }
}
