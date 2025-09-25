<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use InvalidArgumentException;
use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Exception\MustBeOneDimensionalArray;
use ParagonIE\EasyDB\Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(EasyDB::class)]
class Is1DArrayTest extends EasyDBTestCase
{

    /**
     * @dataProvider goodFactoryCreateArgumentProvider
     * @param $dsn
     * @param null $username
     * @param null $password
     * @param array $options
     */
    #[DataProvider("goodFactoryCreateArgumentProvider")]
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
    #[DataProvider("goodFactoryCreateArgumentProvider")]
    public function testColumnThrowsException($expectedDriver, $dsn, $username = null, $password = null, $options = [])
    {
        $db = Factory::create($dsn, $username, $password, $options);
        $this->expectException(MustBeOneDimensionalArray::class);
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
    #[DataProvider("goodFactoryCreateArgumentProvider")]
    public function testSafeQueryThrowsException(
        $expectedDriver,
        $dsn,
        $username = null,
        $password = null,
        $options = []
    ) {
        $db = Factory::create($dsn, $username, $password, $options);
        $this->expectException(MustBeOneDimensionalArray::class);
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
    #[DataProvider("goodFactoryCreateArgumentProvider")]
    public function testSingleThrowsException($expectedDriver, $dsn, $username = null, $password = null, $options = [])
    {
        $db = Factory::create($dsn, $username, $password, $options);
        $this->expectException(MustBeOneDimensionalArray::class);
        $db->single('SELECT "column"', [[1]]);
    }
}
