<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(EasyDB::class)]
class ColTest extends EasyDBTestCase
{
    public static function goodColArguments(): array
    {
        return [
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
    }

    /**
    * EasyDB data provider
    * Returns an array of callables that return instances of EasyDB
    * @return callable[]
    * @see EasyDBTestCase::goodFactoryCreateArgument2EasyDBProvider()
    */
    public static function goodColArgumentsProvider()
    {
        $argsArray = static::goodColArguments();
        return array_reduce(
            static::goodFactoryCreateArgument2EasyDBProvider(),
            function (array $was, array $cbArgs) use ($argsArray) {
                foreach ($argsArray as $args) {
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
     * @param EasyDB $db
     * @param $statement
     * @param $offset
     * @param $params
     * @return mixed
     */
    protected function getResultForMethod(EasyDB $db, $statement, $offset, $params)
    {
        $args = $params;
        array_unshift($args, $statement, $offset);

        return call_user_func_array([$db, 'col'], $args);
    }

    /**
     * @param callable $cb
     * @param string $statement
     * @param int $offset
     * @param array $params
     * @param array $expectedResult
     *
     * @dataProvider goodColArgumentsProvider
     */
    #[DataProvider("goodColArgumentsProvider")]
    public function testMethod(callable $cb, $statement, $offset, $params, $expectedResult)
    {
        $db = $this->easyDBExpectedFromCallable($cb);

        $result = $this->getResultForMethod($db, $statement, $offset, $params);

        $this->assertEquals(array_diff_assoc($result, $expectedResult), []);
    }
}
