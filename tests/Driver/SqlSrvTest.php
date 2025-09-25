<?php
declare(strict_types=1);
namespace ParagonIE\EasyDB\Tests\Driver;

class SqlSrvTest extends BaseTest
{
    /**
     * @return string
     */
    protected function getDsn(): string
    {
        $host = \getenv('MSSQL_HOST') ?: '127.0.0.1';
        $db = \getenv('MSSQL_DB') ?: 'easydb';
        return "sqlsrv:Server={$host};Database={$db}";
    }

    /**
     * @return string
     */
    protected function getUsername(): string
    {
        return \getenv('MSSQL_USER') ?: 'sa';
    }

    /**
     * @return string
     */
    protected function getPassword(): string
    {
        return \getenv('MSSQL_PASS') ?: '';
    }
}
