<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\Exception\ConstructorFailed;
use ParagonIE\EasyDB\Factory;

class ConstructorFailedTest extends EasyDBTest
{

    /**
     * @dataProvider badFactoryCreateArgumentProvider
     * @param $dsn
     * @param null $username
     * @param null $password
     * @param array $options
     */
    public function testConstructorFailed($dsn, $username = null, $password = null, $options = [])
    {
        $this->expectException(ConstructorFailed::class);
        Factory::create($dsn, $username, $password, $options);
    }
}
