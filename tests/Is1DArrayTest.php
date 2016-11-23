<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use InvalidArgumentException;
use ParagonIE\EasyDB\Factory;

class Is1DArrayTest extends
        EasyDBTest
{

    /**
     * @dataProvider GoodFactoryCreateArgumentProvider
     * @param $dsn
     * @param null $username
     * @param null $password
     * @param array $options
     */
    public function testIs1DArray($dsn, $username=null, $password=null, $options = [])
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
     * @dataProvider GoodFactoryCreateArgumentProvider
     * @depends      testIs1DArray
     * @param $dsn
     * @param null $username
     * @param null $password
     * @param array $options
     */
    public function testColumnThrowsException($dsn, $username=null, $password=null, $options = [])
    {
        $db = Factory::create($dsn, $username, $password, $options);
        $this->expectException(InvalidArgumentException::class);
        $db->column('SELECT "column"', [[1]]);
    }

    /**
     * @dataProvider GoodFactoryCreateArgumentProvider
     * @depends      testIs1DArray
     * @param $dsn
     * @param null $username
     * @param null $password
     * @param array $options
     */
    public function testSafeQueryThrowsException($dsn, $username=null, $password=null, $options = [])
    {
        $db = Factory::create($dsn, $username, $password, $options);
        $this->expectException(InvalidArgumentException::class);
        $db->safeQuery('SELECT ?', [[1]]);
    }

    /**
     * @dataProvider GoodFactoryCreateArgumentProvider
     * @depends      testIs1DArray
     * @param $dsn
     * @param null $username
     * @param null $password
     * @param array $options
     */
    public function testSingleThrowsException($dsn, $username=null, $password=null, $options = [])
    {
        $db = Factory::create($dsn, $username, $password, $options);
        $this->expectException(InvalidArgumentException::class);
        $db->single('SELECT "column"', [[1]]);
    }
}
