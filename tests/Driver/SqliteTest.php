<?php
declare(strict_types=1);
namespace ParagonIE\EasyDB\Tests\Driver;

class SqliteTest extends BaseTest
{
    /**
     * @return string
     */
    protected function getDsn(): string
    {
        return 'sqlite::memory:';
    }
}
