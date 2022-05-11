<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use InvalidArgumentException;
use ParagonIE\EasyDB\Exception\InvalidIdentifier;
use ParagonIE\EasyDB\Exception\InvalidTableName;
use ParagonIE\EasyDB\Exception\MustBeOneDimensionalArray;

class Is1DArrayThenDeleteReadOnlyTest extends EasyDBTest
{

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @depends      ParagonIE\EasyDB\Tests\Is1DArrayTest::testIs1DArray
     * @param callable $cb
     */
    public function testDeleteThrowsException(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(MustBeOneDimensionalArray::class);
        $db->delete('irrelevant_but_valid_tablename', [[1]]);
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    public function testDeleteTableNameEmptyThrowsException(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(InvalidTableName::class);
        $db->delete('', ['foo' => 'bar']);
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    public function testDeleteTableNameInvalidThrowsException(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(InvalidIdentifier::class);
        $db->delete('1foo', ['foo' => 'bar']);
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    public function testDeleteConditionsReturnsNull(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->assertEquals(
            $db->delete('irrelevant_but_valid_tablename', []),
            null
        );
    }
}
