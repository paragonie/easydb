<?php
declare (strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use InvalidArgumentException;
use ParagonIE\EasyDB\EasyDB;
use PDOException;

class InsertGetTest
    extends
        InsertTest
{

    /**
    * @dataProvider GoodFactoryCreateArgument2EasyDBProvider
    */
    public function testInsertGetTableNameThrowsException(callable $cb)
    {
        $db = $this->EasyDBExpectedFromCallable($cb);
        $this->expectException(InvalidArgumentException::class);
        $db->insertGet('', ['foo' => 1], 'foo');
    }

    /**
    * @dataProvider GoodFactoryCreateArgument2EasyDBProvider
    */
    public function testInsertGetMapArgThrowsException(callable $cb)
    {
        $db = $this->EasyDBExpectedFromCallable($cb);
        $this->expectException(InvalidArgumentException::class);
        $db->insertGet('irrelevant_but_valid_tablename', [[1]], 'foo');
    }

    /**
    * @dataProvider GoodFactoryCreateArgument2EasyDBProvider
    */
    public function testInsertGetMapArgKeysThrowsException(callable $cb)
    {
        $db = $this->EasyDBExpectedFromCallable($cb);
        $this->expectException(InvalidArgumentException::class);
        $db->insertGet('irrelevant_but_valid_tablename', ['1foo' => 1], '1foo');
    }

    /**
    * @dataProvider GoodFactoryCreateArgument2EasyDBProvider
    */
    public function testInsertGet(callable $cb)
    {
        $db = $this->EasyDBExpectedFromCallable($cb);
        $this->assertEquals(
            $db->insertGet('irrelevant_but_valid_tablename', ['foo' => 'bar'], 'bar'),
            'bar'
        );
    }
}
