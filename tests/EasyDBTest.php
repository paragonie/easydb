<?php
namespace ParagonIE\EasyDB\Tests;

use PHPUnit_Framework_TestCase;

/**
 * Class EasyDBTest
 * @package ParagonIE\EasyDB\Tests
 */
abstract class EasyDBTest
    extends
        PHPUnit_Framework_TestCase
{

    /**
    * Data provider for arguments to be passed to Factory::create
    * These arguments will not result in a valid EasyDB instance
    * @return array
    */
    public function BadFactoryCreateArgumentProvider()
    {
        return [
            [
                'this-dsn-will-fail',
                'username',
                'putastrongpasswordhere'
            ],
        ];
    }

    /**
    * Data provider for arguments to be passed to Factory::create
    * These arguments will result in a valid EasyDB instance
    * @return array
    */
    public function GoodFactoryCreateArgumentProvider()
    {
        return [
            [
                'sqlite::memory:',
                null,
                null,
                [],
                'sqlite'
            ],
        ];
    }
}
