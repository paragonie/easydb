<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\EasyStatement;
use ParagonIE\EasyDB\Exception\MustBeOneDimensionalArray;
use ParagonIE\EasyDB\Factory;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(EasyDB::class)]
#[CoversClass(Factory::class)]
#[CoversClass(MustBeOneDimensionalArray::class)]
class SafeQueryTest extends RunTest
{
    protected function getResultForMethod(EasyDB $db, $statement, $offset, $params)
    {
        return $db->safeQuery($statement, $params);
    }

    /**
     * @dataProvider goodFactoryCreateArgumentProvider
     * @param $dsn
     * @param null $username
     * @param null $password
     * @param array $options
     */
    #[DataProvider("goodFactoryCreateArgumentProvider")]
    public function testSafeQueryCalledWithVariadicParamsThrowsException(
        $expectedDriver,
        $dsn,
        $username = null,
        $password = null,
        array $options = []
    ) {
        $db = Factory::create($dsn, $username, $password, $options);
        $args = [1, 2, 3, 4];
        $results = $db->run('SELECT ? AS foo, ? AS bar UNION SELECT ? AS foo, ? AS bar', ...$args);
        $this->assertIsArray($results);

        $expectedResult = [['foo' => 1, 'bar' => 2], ['foo' => 3, 'bar' => 4]];

        foreach ($results as $i => $result) {
            $this->assertIsArray($result);
            $this->assertEquals(array_diff_assoc($result, $expectedResult[$i]), []);
        }

        $this->expectException(MustBeOneDimensionalArray::class);
        $this->expectExceptionMessage(
            'Only one-dimensional arrays are allowed, please use ' .
            EasyDB::class .
            '::safeQuery()'
        );
        $db->run('SELECT ? AS foo, ? AS bar UNION SELECT ? AS foo, ? AS bar', $args);
    }

    private function database(): array
    {
        $pdo = new PDO('sqlite::memory:');
        $db = new EasyDB($pdo);
        $pdo->exec('CREATE TABLE entries (id INTEGER PRIMARY KEY, a INTEGER, b INTEGER, mark TEXT)');
        $pdo->exec("INSERT INTO entries VALUES (1,1,2,'old'), (2,2,1,'old')");
        $where = EasyStatement::open()->andWithString('a = :a AND b = :b', b: 2, a: 1);
        $this->assertEquals([1], array_column($db->safeQuery('SELECT id FROM entries WHERE ' . $where, $where->values()), 'id'));
        return [$db, $where];
    }

    public function testDeleteUsesTheRowSelectedByNamedBindings(): void
    {
        [$db, $where] = $this->database();
        $this->assertSame(1, $db->delete('entries', $where));
        $this->assertEquals([2], array_column($db->safeQuery('SELECT id FROM entries ORDER BY id'), 'id'));
    }

    public function testUpdateUsesTheRowSelectedByNamedBindings(): void
    {
        [$db, $where] = $this->database();
        $this->assertSame(1, $db->update('entries', ['mark' => 'changed'], $where));
        $this->assertEquals([1], array_column($db->safeQuery("SELECT id FROM entries WHERE mark = 'changed'"), 'id'));
    }
}
