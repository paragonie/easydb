<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use InvalidArgumentException;
use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Exception\InvalidIdentifier;
use ParagonIE\EasyDB\Exception\MustBeOneDimensionalArray;
use ParagonIE\EasyDB\Factory;
use PDOException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(EasyDB::class)]
#[CoversClass(Factory::class)]
#[CoversClass(MustBeOneDimensionalArray::class)]
class InsertManyTest extends EasyDBWriteTestCase
{

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testInsertManyNoFieldsThrowsException(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(InvalidArgumentException::class);
        $this->assertFalse($db->insertMany('irrelevant_but_valid_tablename', []));
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testInsertManyNoFieldsThrowsPdoException(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(PDOException::class);
        $db->insertMany('irrelevant_but_valid_tablename', [[], [1]]);
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testInsertManyArgTableThrowsException(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(InvalidIdentifier::class);
        $db->insertMany('', [['foo' => 1], ['foo' => 2]]);
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testInsertManyArgMapKeysThrowsException(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(InvalidIdentifier::class);
        $db->insertMany('irrelevant_but_valid_tablename', [['1foo' => 1]]);
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testInsertManyArgMapIs1DArrayThrowsException(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(MustBeOneDimensionalArray::class);
        $db->insertMany('irrelevant_but_valid_tablename', [['foo' => [1]]]);
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testInsertMany(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $db->insertMany('irrelevant_but_valid_tablename', [['foo' => '1'], ['foo' => '2']]);
        $this->assertEquals(
            2,
            $db->single('SELECT COUNT(*) FROM irrelevant_but_valid_tablename')
        );
    }
}
