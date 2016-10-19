<?php
declare (strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use InvalidArgumentException;
use ParagonIE\EasyDB\EasyDB;
use PDOException;

class InsertManyTest
    extends
        EasyDBWriteTest
{

    /**
    * @dataProvider GoodFactoryCreateArgument2EasyDBProvider
    */
    public function testInsertManyNoFieldsThrowsException(callable $cb)
    {
        $db = $this->EasyDBExpectedFromCallable($cb);
        $this->expectException(PDOException::class);
        $this->assertFalse($db->insertMany('irrelevant_but_valid_tablename', []));
    }

    /**
    * @dataProvider GoodFactoryCreateArgument2EasyDBProvider
    */
    public function testInsertManyThrowsException(callable $cb)
    {
        $db = $this->EasyDBExpectedFromCallable($cb);
        $this->expectException(InvalidArgumentException::class);
        $db->insertMany('irrelevant_but_valid_tablename', [[], [1]]);
    }

    /**
    * @dataProvider GoodFactoryCreateArgument2EasyDBProvider
    */
    public function testInsertManyArgTableThrowsException(callable $cb)
    {
        $db = $this->EasyDBExpectedFromCallable($cb);
        $this->expectException(InvalidArgumentException::class);
        $db->insertMany('', [['foo' => 1], ['foo' => 2]]);
    }

    /**
    * @dataProvider GoodFactoryCreateArgument2EasyDBProvider
    */
    public function testInsertManyArgMapKeysThrowsException(callable $cb)
    {
        $db = $this->EasyDBExpectedFromCallable($cb);
        $this->expectException(InvalidArgumentException::class);
        $db->insertMany('irrelevant_but_valid_tablename', [['1foo' => 1]]);
    }

    /**
    * @dataProvider GoodFactoryCreateArgument2EasyDBProvider
    */
    public function testInsertManyArgMapIs1DArrayThrowsException(callable $cb)
    {
        $db = $this->EasyDBExpectedFromCallable($cb);
        $this->expectException(InvalidArgumentException::class);
        $db->insertMany('irrelevant_but_valid_tablename', [['foo' => [1]]]);
    }

    /**
    * @dataProvider GoodFactoryCreateArgument2EasyDBProvider
    */
    public function testInsertMany(callable $cb)
    {
        $db = $this->EasyDBExpectedFromCallable($cb);
        $db->insertMany('irrelevant_but_valid_tablename', [['foo' => '1'], ['foo' => '2']]);
        $this->assertEquals(
            $db->single('SELECT COUNT(*) FROM irrelevant_but_valid_tablename'),
            2
        );
    }
}
