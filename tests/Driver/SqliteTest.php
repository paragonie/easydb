<?php
declare(strict_types=1);
namespace ParagonIE\EasyDB\Tests\Driver;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(EasyDB::class)]
#[CoversClass(Factory::class)]
class SqliteTest extends DriverTestCase
{
    protected function getDsn(): string
    {
        return 'sqlite::memory:';
    }
}
