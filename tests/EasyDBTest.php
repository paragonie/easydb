<?php
namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\Factory;
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
    * Data provider for EasyDB instances
    * @return array
    */
    public function EasyDBProvider()
    {
        return [
            [
                Factory::create('sqlite::memory:')
            ],
        ];
    }

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
}
