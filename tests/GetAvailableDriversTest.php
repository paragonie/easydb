<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(EasyDB::class)]
class GetAvailableDriversTest extends EasyDBTestCase
{

    /**
     * @param $expectedDriver
     * @param $dsn
     * @param null $username
     * @param null $password
     * @param array $options
     * @dataProvider goodFactoryCreateArgumentProvider
     */
    #[DataProvider("goodFactoryCreateArgumentProvider")]
    public function testGetAvailableDrivers(
        $expectedDriver,
        $dsn,
        $username = null,
        $password = null,
        array $options = []
    ) {
        if (count(PDO::getAvailableDrivers()) < 1) {
            $this->markTestSkipped('No drivers available!');
        } else {
            $db = Factory::create($dsn, $username, $password, $options);
            $this->assertCount(
                0,
                array_diff_assoc(
                    PDO::getAvailableDrivers(),
                    $db->getAvailableDrivers()
                )
            );
            $this->assertCount(
                0,
                array_diff_assoc(
                    PDO::getAvailableDrivers(),
                    $db->getPdo()->getAvailableDrivers()
                )
            );
            $this->assertCount(
                0,
                array_diff_assoc(
                    $db->getAvailableDrivers(),
                    $db->getPdo()->getAvailableDrivers()
                )
            );
        }
    }
}
