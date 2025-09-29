<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Exception\MustBeOneDimensionalArray;
use ParagonIE\EasyDB\Factory;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(EasyDB::class)]
#[CoversClass(Factory::class)]
#[CoversClass(MustBeOneDimensionalArray::class)]
class RowTest extends SafeQueryTest
{
    protected function getResultForMethod(EasyDB $db, $statement, $offset, $params)
    {
        $args = $params;
        array_unshift($args, $statement);

        return call_user_func_array([$db, 'row'], $args);
    }

    #[DataProvider("goodFactoryCreateArgumentProvider")]
    public function testRowWithDifferentFetchStyle(
        string $expectedDriver,
        string $dsn,
        ?string $username = null,
        ?string $password = null,
        array $options = []
    ): void {
        $options[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_OBJ;
        $db = Factory::create($dsn, $username, $password, $options);
        $args = [1, 2, 3, 4];
        $results = $db->row('SELECT ? AS foo, ? AS bar UNION SELECT ? AS foo, ? AS bar', ...$args);
        $this->assertIsObject($results);
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

        $result = $this->getResultForMethod($db, $statement, $offset, $params);

        $this->assertEquals([], array_diff_assoc($result, $expectedResult[0]));
    }
}
