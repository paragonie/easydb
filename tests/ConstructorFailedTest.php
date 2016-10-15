<?php
declare (strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\Exception\ConstructorFailed;
use ParagonIE\EasyDB\Factory;

class ConstructorFailedTest
    extends
        EasyDBTest
{

    /**
    * @dataProvider BadFactoryCreateArgumentProvider
    */
    public function testConstructorFailed($dsn, $username=null, $password=null, $options = array())
    {
        $this->expectException(ConstructorFailed::class);
        Factory::create($dsn, $username, $password, $options);
    }
}
