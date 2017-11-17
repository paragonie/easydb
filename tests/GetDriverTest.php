<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\Factory;

class GetDriverTest extends EasyDBTest
{
    /**
     * @param $dsn
     * @param string|null $username
     * @param string|null $password
     * @param array $options
     * @param string $expectedDriver
     *
     * @dataProvider goodFactoryCreateArgumentProvider
     */
    public function testGetDriver(
        $expectedDriver,
        $dsn,
        $username = null,
        $password = null,
        $options = []
    ) {
        $db = Factory::create($dsn, $username, $password, $options);
        $this->assertEquals($db->getDriver(), $expectedDriver);
    }
}
