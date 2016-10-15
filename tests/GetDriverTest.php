<?php
declare (strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\Factory;

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
        $this->assertEquals($db->getDriver(), $expectedDriver);
    }
}
