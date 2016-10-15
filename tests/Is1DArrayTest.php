<?php
namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;

class Is1DArrayTest
    extends
        EasyDBTest
{

    /**
    * @dataProvider GoodFactoryCreateArgumentProvider
    */
    public function testIs1DArray($dsn, $username=null, $password=null, $options = array())
    {
        $db = Factory::create($dsn, $username, $password, $options);
        $this->assertInstanceOf(EasyDB::class, $db);
        $this->assertTrue($db->is1DArray([]));
        $this->assertFalse($db->is1DArray([[]]));
        $this->assertFalse($db->is1DArray([[],[]]));
        $this->assertTrue($db->is1DArray([1]));
        $this->assertFalse($db->is1DArray([[1]]));
        $this->assertFalse($db->is1DArray([[1],[2]]));
    }
}
