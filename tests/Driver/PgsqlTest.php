<?php

namespace ParagonIE\EasyDB\Tests\Driver;

class PgsqlTest extends DriverTestCase
{
    /**
     * @return string
     */
    protected function getDsn(): string
    {
        $host = \getenv('PGSQL_HOST') ?: '127.0.0.1';
        $db = \getenv('PGSQL_DB') ?: 'easydb';
        return "pgsql:host={$host};dbname={$db}";
    }

    /**
     * @return string
     */
    protected function getUsername(): string
    {
        return \getenv('PGSQL_USER') ?: 'postgres';
    }

    /**
     * @return string
     */
    protected function getPassword(): string
    {
        return \getenv('PGSQL_PASS') ?: 'password';
    }
}
