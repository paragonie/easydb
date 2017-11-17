<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use InvalidArgumentException;
use ParagonIE\EasyDB\Factory;

class Is1DArrayTest extends EasyDBTest
{

    /**
     * @dataProvider goodFactoryCreateArgumentProvider
     * @param $dsn
     * @param null $username
     * @param null $password
     * @param array $options
     */
    public function testIs1DArray($expectedDriver, $dsn, $username = null, $password = null, $options = [])
    {
        $db = Factory::create($dsn, $username, $password, $options);
        $this->assertTrue($db->is1DArray([]));
        $this->assertFalse($db->is1DArray([[]]));
        $this->assertFalse($db->is1DArray([[], []]));
        $this->assertTrue($db->is1DArray([1]));
        $this->assertFalse($db->is1DArray([[1]]));
        $this->assertFalse($db->is1DArray([[1], [2]]));
    }

    /**
     * @dataProvider goodFactoryCreateArgumentProvider
     * @depends      testIs1DArray
     * @param $dsn
     * @param null $username
     * @param null $password
     * @param array $options
     */
    public function testColumnThrowsException($expectedDriver, $dsn, $username = null, $password = null, $options = [])
    {
        $db = Factory::create($dsn, $username, $password, $options);
        $this->expectException(InvalidArgumentException::class);
        $db->column('SELECT "column"', [[1]]);
    }

    /**
     * @dataProvider goodFactoryCreateArgumentProvider
     * @depends      testIs1DArray
     * @param $dsn
     * @param null $username
     * @param null $password
     * @param array $options
     */
    public function testSafeQueryThrowsException(
        $expectedDriver,
        $dsn,
        $username = null,
        $password = null,
        $options = []
    ) {
        $db = Factory::create($dsn, $username, $password, $options);
        $this->expectException(InvalidArgumentException::class);
        $db->safeQuery('SELECT ?', [[1]]);
    }

    /**
     * @dataProvider goodFactoryCreateArgumentProvider
     * @depends      testIs1DArray
     * @param $dsn
     * @param null $username
     * @param null $password
     * @param array $options
     */
    public function testSingleThrowsException($expectedDriver, $dsn, $username = null, $password = null, $options = [])
    {
        $db = Factory::create($dsn, $username, $password, $options);
        $this->expectException(InvalidArgumentException::class);
        $db->single('SELECT "column"', [[1]]);
    }
}
