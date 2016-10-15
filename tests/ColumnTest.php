<?php
namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use PDO;
use PDOException;

class ColumnTest
    extends
        ColTest
{

    /**
    * @dataProvider colArgumentsProvider
    */
    public function testMethod(callable $cb, $statement, $offset, $params, $expectedResult)
    {
        $db = $cb();
        $this->assertInstanceOf(EasyDB::class, $db);

        $args = $params;
        array_unshift($args, $statement, $offset);

        $result = $db->column($statement, $params, $offset);

        $this->assertEquals(array_diff_assoc($result, $expectedResult), []);
    }
}
