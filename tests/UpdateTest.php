<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use InvalidArgumentException;
use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\EasyStatement;
use ParagonIE\EasyDB\Exception\InvalidIdentifier;
use ParagonIE\EasyDB\Exception\InvalidTableName;
use ParagonIE\EasyDB\Exception\MustBeOneDimensionalArray;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(EasyDB::class)]
class UpdateTest extends EasyDBWriteTestCase
{

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testUpdateArgChangesReturnsNull(callable $cb): void
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->assertEquals(
            $db->update('irrelevant_but_valid_tablename', [], ['1=1']),
            null
        );
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testUpdateArgConditionsReturnsNull(callable $cb): void
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->assertEquals(
            $db->update('irrelevant_but_valid_tablename', ['foo' => 'bar'], []),
            null
        );
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testUpdateArgChangesThrowsException(callable $cb): void
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(MustBeOneDimensionalArray::class);
        $db->update('irrelevant_but_valid_tablename', [[1]], ['TRUE' => true]);
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testUpdateArgConditionsThrowsException(callable $cb): void
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(MustBeOneDimensionalArray::class);
        $db->update('irrelevant_but_valid_tablename', ['1=1'], [[1]]);
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testUpdateTableNameThrowsException(callable $cb): void
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(InvalidTableName::class);
        $db->update('', ['foo' => 'bar'], ['TRUE' => true]);
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testUpdateArgChangesKeyThrowsException(callable $cb): void
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(InvalidIdentifier::class);
        $db->update('irrelevant_but_valid_tablename', ['1foo' => 1], ['TRUE' => true]);
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testUpdateArgConditionsKeyThrowsException(callable $cb): void
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(InvalidIdentifier::class);
        $db->update('irrelevant_but_valid_tablename', ['foo' => 1], ['1foo' => true]);
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testUpdateEasyStatement(callable $cb): void
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $db->insertMany('irrelevant_but_valid_tablename', [['foo' => '1'], ['foo' => '2']]);
        $this->assertEquals(
            $db->single('SELECT COUNT(*) FROM irrelevant_but_valid_tablename'),
            2
        );
        $this->assertEquals(
            $db->single('SELECT COUNT(*) FROM irrelevant_but_valid_tablename WHERE foo = ?', [1]),
            1
        );
        $this->assertEquals(
            $db->single('SELECT COUNT(*) FROM irrelevant_but_valid_tablename WHERE foo = ?', [2]),
            1
        );
        $this->assertEquals(
            $db->single('SELECT COUNT(*) FROM irrelevant_but_valid_tablename WHERE foo = ?', [3]),
            0
        );
        $easyStatement = EasyStatement::open()->with('foo = ?', 2);
        $db->update('irrelevant_but_valid_tablename', ['foo' => 3], $easyStatement);
        $this->assertEquals(
            $db->single('SELECT COUNT(*) FROM irrelevant_but_valid_tablename WHERE foo = ?', [2]),
            0
        );
        $this->assertEquals(
            $db->single('SELECT COUNT(*) FROM irrelevant_but_valid_tablename WHERE foo = ?', [3]),
            1
        );
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testUpdate(callable $cb): void
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $db->insertMany('irrelevant_but_valid_tablename', [['foo' => '1'], ['foo' => '2']]);
        $this->assertEquals(
            2,
            $db->single('SELECT COUNT(*) FROM irrelevant_but_valid_tablename')
        );
        $this->assertEquals(
            1,
            $db->single('SELECT COUNT(*) FROM irrelevant_but_valid_tablename WHERE foo = ?', [1])
        );
        $this->assertEquals(
            1,
            $db->single('SELECT COUNT(*) FROM irrelevant_but_valid_tablename WHERE foo = ?', [2])
        );
        $this->assertEquals(
            0,
            $db->single('SELECT COUNT(*) FROM irrelevant_but_valid_tablename WHERE foo = ?', [3])
        );
        $db->update('irrelevant_but_valid_tablename', ['foo' => 3], ['foo' => 2]);
        $this->assertEquals(
            0,
            $db->single('SELECT COUNT(*) FROM irrelevant_but_valid_tablename WHERE foo = ?', [2])
        );
        $this->assertEquals(
            1,
            $db->single('SELECT COUNT(*) FROM irrelevant_but_valid_tablename WHERE foo = ?', [3])
        );
    }
}
