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
    public function testInsertManyReturnsNull(callable $cb)
    {
        $db = $this->EasyDBExpectedFromCallable($cb);

        $this->assertNull($db->insertMany('irrelevant_but_valid_tablename', []));
    }
}
