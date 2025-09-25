<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\Factory;

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
