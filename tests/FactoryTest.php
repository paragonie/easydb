<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use PHPUnit\Framework\TestCase;

/**
 * Class FactoryTest
 * @package ParagonIE\EasyDB\Tests
 */
class FactoryTest extends TestCase
{
    public function testFactoryCreate()
    {
        $this->assertInstanceOf(
            EasyDB::class,
            Factory::create('sqlite::memory:')
        );
    }
}
