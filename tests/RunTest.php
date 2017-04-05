<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;

class RunTest extends ColTest
{
    protected function goodColArguments()
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
    public function testMethod(callable $cb, $statement, $offset, $params, $expectedResult)
    {
        $db = $this->easyDBExpectedFromCallable($cb);

        $results = $this->getResultForMethod($db, $statement, $offset, $params);

        foreach ($results as $i => $result) {
            $this->assertEquals(array_diff_assoc($result, $expectedResult[$i]), []);
        }
    }
}
