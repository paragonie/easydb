<?php
namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use PDO;
use PDOException;

class GetDriverTest
    extends
        EasyDBTest
{

    /**
    * @dataProvider GoodFactoryCreateArgumentProvider
    */
    public function testGetDriver($dsn, $username=null, $password=null, $options = array(), $expectedDriver)
    {
        $db = Factory::create($dsn, $username, $password, $options);
        $this->assertInstanceOf(EasyDB::class, $db);
        $this->assertEquals($db->getDriver(), $expectedDriver);
    }
}
