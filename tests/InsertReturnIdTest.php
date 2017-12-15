<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use InvalidArgumentException;

class InsertReturnIdTest extends InsertTest
{

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    public function testInsertReturnIdTableNameThrowsException(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(InvalidArgumentException::class);
        $db->insertReturnId('', ['foo' => 1], 'foo');
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    public function testInsertReturnIdMapArgThrowsException(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(InvalidArgumentException::class);
        $db->insertReturnId('irrelevant_but_valid_tablename', [[1]], 'foo');
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    public function testInsertReturnIdMapArgKeysThrowsException(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(InvalidArgumentException::class);
        $db->insertReturnId('irrelevant_but_valid_tablename', ['1foo' => 1], '1foo');
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    public function testInsertReturnId(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->assertEquals(
            $db->insertReturnId('irrelevant_but_valid_tablename', ['foo' => 'bar']),
            '1'
        );
        $this->assertEquals(
            $db->insertReturnId('irrelevant_but_valid_tablename', ['foo' => 'bar2']),
            '2'
        );
    }
    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    public function testInsertReturnIdException(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(\Exception::class);
        $this->assertEquals(
            $db->insertReturnId('irrelevant_but_valid_tablename', [], 'bar'),
            'bar'
        );
    }
}
