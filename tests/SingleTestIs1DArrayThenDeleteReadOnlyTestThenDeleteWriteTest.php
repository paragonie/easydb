<?php
declare (strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use InvalidArgumentException;
use ParagonIE\EasyDB\EasyDB;

class SingleTestIs1DArrayThenDeleteReadOnlyTestThenDeleteWriteTest
    extends
        EasyDBWriteTest
{

    /**
    *
    */
    public function GoodFactoryCreateArgument2EasyDBProvider()
    {
        $cbArgsSets = parent::GoodFactoryCreateArgument2EasyDBProvider();
        $args = [
            [
                [
                    ['foo' => '1'],
                    ['foo' => '2'],
                    ['foo' => '3'],
                ],
            ],
        ];

        return array_reduce(
            $args,
            function (array $was, array $is) use ($cbArgsSets) {

                foreach ($cbArgsSets as $cbArgs) {
                    $args = array_values($is);
                    foreach (array_reverse($cbArgs) as $cbArg) {
                        array_unshift($args, $cbArg);
                    }
                    $was[] = $args;
                }

                return $was;
            },
            []
        );
    }

    /**
    * @dataProvider GoodFactoryCreateArgument2EasyDBProvider
    * @depends ParagonIE\EasyDB\Tests\Is1DArrayThenDeleteReadOnlyTest::testDeleteThrowsException
    * @depends ParagonIE\EasyDB\Tests\Is1DArrayThenDeleteReadOnlyTest::testDeleteTableNameEmptyThrowsException
    * @depends ParagonIE\EasyDB\Tests\Is1DArrayThenDeleteReadOnlyTest::testDeleteTableNameInvalidThrowsException
    * @depends ParagonIE\EasyDB\Tests\Is1DArrayThenDeleteReadOnlyTest::testDeleteConditionsReturnsNull
    * @depends ParagonIE\EasyDB\Tests\InsertManyTest::testInsertMany
    * @depends ParagonIE\EasyDB\Tests\SingleTest::testMethod
    */
    public function testDelete(callable $cb, array $insertMany)
    {
        $db = $this->EasyDBExpectedFromCallable($cb);
        $db->insertMany('irrelevant_but_valid_tablename', $insertMany);
        $insertManyTotal = count($insertMany);
        $this->assertEquals(
            $db->single('SELECT COUNT(*) FROM irrelevant_but_valid_tablename'),
            $insertManyTotal
        );
        foreach ($insertMany as $insertVal) {
            $this->assertEquals(
                $db->single(
                    'SELECT COUNT(*) FROM irrelevant_but_valid_tablename WHERE foo = ?',
                    array_values($insertVal)
                ),
                1
            );
        }
        for ($i=0;$i<$insertManyTotal;++$i) {
            $db->delete('irrelevant_but_valid_tablename', $insertMany[$i]);
            $this->assertEquals(
                $db->single('SELECT COUNT(*) FROM irrelevant_but_valid_tablename'),
                ($insertManyTotal - ($i + 1))
            );
        }
    }
}
