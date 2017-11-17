<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use InvalidArgumentException;
use ParagonIE\EasyDB\EasyDB;
use PDOException;

class InsertManyFlatTransactionTest extends
 EasyDBWriteTest
{
    /**
     * @dataProvider GoodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    public function testInsertMany(callable $cb)
    {
        $db = $this->EasyDBExpectedFromCallable($cb);
        $db->insertMany('irrelevant_but_valid_tablename', [['foo' => '1'], ['foo' => '2']]);
        $callbackWillThrow = function (EasyDB $mightNotBeTheOtherDb) {
            $mightNotBeTheOtherDb->insertMany('irrelevant_but_valid_tablename', [['foo' => '3'], ['foo' => '4']]);

            throw new InsertManyFlatTransactionTestRuntimeException(
                'We pretend we made a call to something else that interupts a transaction'
            );
        };
        try {
            $db->tryFlatTransaction($callbackWillThrow);
        } catch (InsertManyFlatTransactionTestRuntimeException $e) {
            // we do nothing here on purpose
        }
        $this->assertEquals(
            $db->single('SELECT COUNT(*) FROM irrelevant_but_valid_tablename'),
            2
        );
    }
}
