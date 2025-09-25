<?php
declare(strict_types=1);
namespace ParagonIE\EasyDB\Tests\Driver;

use ParagonIE\EasyDB\EasyDB;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(EasyDB::class)]
class SqliteTest extends DriverTestCase
{
    protected function getDsn(): string
    {
        return 'sqlite::memory:';
    }
}
