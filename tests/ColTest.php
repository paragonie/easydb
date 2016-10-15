<?php
namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use PDO;
use PDOException;

class ColTest
    extends
        EasyDBTest
{

    /**
    * EasyDB data provider
    * Returns an array of callables that return instances of EasyDB
    * @return array
    * @see EasyDBTest::GoodFactoryCreateArgument2EasyDBProvider()
    */
    public function GoodColArgumentsProvider()
    {
        $argsArray = [
            [
                'SELECT 1 AS foo', 0, [], [1]
            ],
            [
                'SELECT 1 AS foo, 2 AS bar', 0, [], [1]
            ],
            [
                'SELECT 1 AS foo, 2 AS bar', 1, [], [2]
            ],
            [
                'SELECT 1 AS foo, 2 AS bar UNION SELECT 3 AS foo, 4 AS bar', 0, [], [1,3]
            ],
            [
                'SELECT 1 AS foo, 2 AS bar UNION SELECT 3 AS foo, 4 AS bar', 1, [], [2,4]
            ],
            [
                'SELECT ? AS foo, ? AS bar UNION SELECT ? AS foo, ? AS bar', 0, [1, 2, 3, 4], [1, 3]
            ],
            [
                'SELECT ? AS foo, ? AS bar UNION SELECT ? AS foo, ? AS bar', 1, [1, 2, 3, 4], [2, 4]
            ]
        ];
        return array_reduce(
            $this->GoodFactoryCreateArgument2EasyDBProvider(),
            function (array $was, callable $cb) use ($argsArray) {
                foreach ($argsArray as $args) {
                    array_unshift($args, $cb);
                    $was[] = $args;
                }
                return $was;
            },
            []
        );
    }


    protected function getResultForMethod(EasyDB $db, $statement, $offset, $params)
    {
        $args = $params;
        array_unshift($args, $statement, $offset);

        return call_user_func_array([$db, 'col'], $args);
    }

    /**
    * @dataProvider GoodColArgumentsProvider
    */
    public function testMethod(callable $cb, $statement, $offset, $params, $expectedResult)
    {
        $db = $cb();
        $this->assertInstanceOf(EasyDB::class, $db);

        $result = $this->getResultForMethod($db, $statement, $offset, $params);

        $this->assertEquals(array_diff_assoc($result, $expectedResult), []);
    }
}
