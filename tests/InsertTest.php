<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use InvalidArgumentException;
use PDOException;

class InsertTest extends EasyDBWriteTest
{

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    public function testInsertNoFieldsThrowsException(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(PDOException::class);
        $this->assertNull($db->insert('irrelevant_but_valid_tablename', []));
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    public function testInsertTableNameThrowsException(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(InvalidArgumentException::class);
        $db->insert('', ['foo' => 1]);
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    public function testInsertMapArgThrowsException(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(InvalidArgumentException::class);
        $db->insert('irrelevant_but_valid_tablename', [[1]]);
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    public function testInsertMapArgKeysThrowsException(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(InvalidArgumentException::class);
        $db->insert('irrelevant_but_valid_tablename', ['1foo' => 1]);
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    public function testInsertIncorrectFieldThrowsException(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(PDOException::class);
        $db->insert('irrelevant_but_valid_tablename', ['bar' => 1]);
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    public function testInsert(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $db->insert('irrelevant_but_valid_tablename', ['foo' => 1]);
        $this->assertEquals(
            $db->single('SELECT COUNT(foo) FROM irrelevant_but_valid_tablename WHERE foo = ?', [1]),
            '1'
        );
        $db->insert('table_with_bool', ['foo' => 'test', 'bar' => true]);
        $this->assertEquals(
            $db->single('SELECT COUNT(foo) FROM table_with_bool WHERE bar'),
            '1'
        );
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    public function testBuildeInsertSql(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $statement = $db->buildInsertQuery('test_table', ['id', 'col1', 'col2']);
        $expected = '/insert into .test_table. \(.id., .col1., .col2.\) VALUES \(\?, \?, \?\)/i';
        $this->assertRegExp($expected, $statement);
    }
}
