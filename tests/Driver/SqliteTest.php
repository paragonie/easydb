<?php
declare(strict_types=1);
namespace ParagonIE\EasyDB\Tests\Driver;

class SqliteTest extends DriverTestCase
{
    /**
     * @return string
     */
    protected function getDsn(): string
    {
        return 'sqlite::memory:';
    }
}
