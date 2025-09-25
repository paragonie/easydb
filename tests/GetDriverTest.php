<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(EasyDB::class)]
class GetDriverTest extends EasyDBTestCase
{
    /**
     * @param string $expectedDriver
     * @param string $dsn
     * @param string|null $username
     * @param string|null $password
     * @param array $options
     *
     * @dataProvider goodFactoryCreateArgumentProvider
     */
    #[DataProvider("goodFactoryCreateArgumentProvider")]
    public function testGetDriver(
        string $expectedDriver,
        string $dsn,
        ?string $username = null,
        ?string $password = null,
        array $options = []
    ): void {
        $db = Factory::create($dsn, $username, $password, $options);
        $this->assertEquals($db->getDriver(), $expectedDriver);
    }
}
