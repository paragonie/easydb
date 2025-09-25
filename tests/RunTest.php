<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(EasyDB::class)]
#[CoversClass(Factory::class)]
class RunTest extends ColTest
{
    public static function goodColArguments(): array
    {
        return [
            [
                'SELECT 1 AS foo', 0, [], [['foo' => 1]]
            ],
            [
                'SELECT 1 AS foo, 2 AS bar', 0, [], [['foo' => 1, 'bar' => 2]]
            ],
            [
                'SELECT 1 AS foo, 2 AS bar UNION SELECT 3 AS foo, 4 AS bar',
                0,
                [],
                [['foo' => 1, 'bar' => 2], ['foo' => 3, 'bar' => 4]],
            ],
            [
                'SELECT ? AS foo, ? AS bar UNION SELECT ? AS foo, ? AS bar',
                0,
                [1, 2, 3, 4],
                [['foo' => 1, 'bar' => 2], ['foo' => 3, 'bar' => 4]],
            ],
        ];
    }


    protected function getResultForMethod(EasyDB $db, $statement, $offset, $params)
    {
        $args = $params;
        array_unshift($args, $statement);

        return call_user_func_array([$db, 'run'], $args);
    }

    /**
     * @dataProvider goodColArgumentsProvider
     * @param callable $cb
     * @param string $statement
     * @param int $offset
     * @param array $params
     * @param array $expectedResult
     */
    #[DataProvider("goodColArgumentsProvider")]
    public function testMethod(callable $cb, $statement, $offset, $params, $expectedResult): void
    {
        $db = $this->easyDBExpectedFromCallable($cb);

        $results = $this->getResultForMethod($db, $statement, $offset, $params);

        foreach ($results as $i => $result) {
            $this->assertEquals(array_diff_assoc($result, $expectedResult[$i]), []);
        }
    }
}
