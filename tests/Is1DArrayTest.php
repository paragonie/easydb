<?php
declare (strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use InvalidArgumentException;
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
        $this->assertTrue($db->is1DArray([]));
        $this->assertFalse($db->is1DArray([[]]));
        $this->assertFalse($db->is1DArray([[],[]]));
        $this->assertTrue($db->is1DArray([1]));
        $this->assertFalse($db->is1DArray([[1]]));
        $this->assertFalse($db->is1DArray([[1],[2]]));
    }

    /**
    * @dataProvider GoodFactoryCreateArgumentProvider
    * @depends testIs1DArray
    */
    public function testColumnThrowsException($dsn, $username=null, $password=null, $options = array())
    {
        $db = Factory::create($dsn, $username, $password, $options);
        $this->expectException(InvalidArgumentException::class);
        $db->column('SELECT "column"', [[1]]);
    }

    /**
    * @dataProvider GoodFactoryCreateArgumentProvider
    * @depends testIs1DArray
    */
    public function testDeleteThrowsException($dsn, $username=null, $password=null, $options = array())
    {
        $db = Factory::create($dsn, $username, $password, $options);
        $this->expectException(InvalidArgumentException::class);
        $db->delete('irrelevant_but_valid_tablename', [[1]]);
    }

    /**
    * @dataProvider GoodFactoryCreateArgumentProvider
    * @depends testIs1DArray
    */
    public function testEscapeValueSetThrowsException($dsn, $username=null, $password=null, $options = array())
    {
        $db = Factory::create($dsn, $username, $password, $options);
        $this->expectException(InvalidArgumentException::class);
        $db->escapeValueSet([[1]]);
    }

    /**
    * @dataProvider GoodFactoryCreateArgumentProvider
    * @depends testIs1DArray
    */
    public function testInsertThrowsException($dsn, $username=null, $password=null, $options = array())
    {
        $db = Factory::create($dsn, $username, $password, $options);
        $this->expectException(InvalidArgumentException::class);
        $db->insert('irrelevant_but_valid_tablename', [[1]]);
    }

    /**
    * @dataProvider GoodFactoryCreateArgumentProvider
    * @depends testIs1DArray
    */
    public function testInsertManyThrowsException($dsn, $username=null, $password=null, $options = array())
    {
        $db = Factory::create($dsn, $username, $password, $options);
        $this->expectException(InvalidArgumentException::class);
        $db->insertMany('irrelevant_but_valid_tablename', [[[2]]]);
    }

    /**
    * @dataProvider GoodFactoryCreateArgumentProvider
    * @depends testIs1DArray
    */
    public function testSafeQueryThrowsException($dsn, $username=null, $password=null, $options = array())
    {
        $db = Factory::create($dsn, $username, $password, $options);
        $this->expectException(InvalidArgumentException::class);
        $db->safeQuery('SELECT ?', [[1]]);
    }

    /**
    * @dataProvider GoodFactoryCreateArgumentProvider
    * @depends testIs1DArray
    */
    public function testSingleThrowsException($dsn, $username=null, $password=null, $options = array())
    {
        $db = Factory::create($dsn, $username, $password, $options);
        $this->expectException(InvalidArgumentException::class);
        $db->single('SELECT "column"', [[1]]);
    }

    /**
    * @dataProvider GoodFactoryCreateArgumentProvider
    * @depends testIs1DArray
    */
    public function testUpdateArgChangesThrowsException($dsn, $username=null, $password=null, $options = array())
    {
        $db = Factory::create($dsn, $username, $password, $options);
        $this->expectException(InvalidArgumentException::class);
        $db->update('irrelevant_but_valid_tablename', [[1]], ['1=1']);
    }

    /**
    * @dataProvider GoodFactoryCreateArgumentProvider
    * @depends testIs1DArray
    */
    public function testUpdateArgConditionsThrowsException($dsn, $username=null, $password=null, $options = array())
    {
        $db = Factory::create($dsn, $username, $password, $options);
        $this->expectException(InvalidArgumentException::class);
        $db->update('irrelevant_but_valid_tablename', ['1=1'], [[1]]);
    }
}
