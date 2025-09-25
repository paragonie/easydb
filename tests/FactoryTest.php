<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class FactoryTest
 * @package ParagonIE\EasyDB\Tests
 */
#[CoversClass(EasyDB::class)]
#[CoversClass(Factory::class)]
class FactoryTest extends TestCase
{
    public function testFactoryCreate(): void
    {
        $this->assertInstanceOf(
            EasyDB::class,
            Factory::create('sqlite::memory:')
        );
    }
}
