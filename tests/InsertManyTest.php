<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use InvalidArgumentException;
use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Exception\InvalidIdentifier;
use ParagonIE\EasyDB\Exception\MustBeOneDimensionalArray;
use ParagonIE\EasyDB\Factory;
use PDOException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PDO;

#[CoversClass(EasyDB::class)]
#[CoversClass(Factory::class)]
#[CoversClass(MustBeOneDimensionalArray::class)]
class InsertManyTest extends EasyDBWriteTestCase
{

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testInsertManyNoFieldsThrowsException(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(InvalidArgumentException::class);
        $this->assertFalse($db->insertMany('irrelevant_but_valid_tablename', []));
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testInsertManyNoFieldsThrowsPdoException(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(PDOException::class);
        $db->insertMany('irrelevant_but_valid_tablename', [[], [1]]);
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testInsertManyArgTableThrowsException(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(InvalidIdentifier::class);
        $db->insertMany('', [['foo' => 1], ['foo' => 2]]);
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testInsertManyArgMapKeysThrowsException(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(InvalidIdentifier::class);
        $db->insertMany('irrelevant_but_valid_tablename', [['1foo' => 1]]);
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testInsertManyArgMapIs1DArrayThrowsException(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(MustBeOneDimensionalArray::class);
        $db->insertMany('irrelevant_but_valid_tablename', [['foo' => [1]]]);
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testInsertMany(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $db->insertMany('irrelevant_but_valid_tablename', [['foo' => '1'], ['foo' => '2']]);
        $this->assertEquals(
            2,
            $db->single('SELECT COUNT(*) FROM irrelevant_but_valid_tablename')
        );
    }

    public function testProtectedColumnStaysBoundToItsKey(): void
    {
        $db = new EasyDB(new PDO('sqlite::memory:'));
        $db->run('CREATE TABLE items (tenant_id INTEGER, resource_id INTEGER)');
        $rows = json_decode('[{"tenant_id":0,"resource_id":100},{"resource_id":666,"tenant_id":0}]', true);
        foreach ($rows as &$row) { $row['tenant_id'] = 7; }
        unset($row);
        $db->insertMany('items', $rows);
        $this->assertSame(0, (int) $db->single('SELECT COUNT(*) FROM items WHERE tenant_id <> 7'));
        $this->assertSame([100, 666], array_map('intval', $db->col('SELECT resource_id FROM items ORDER BY resource_id')));
    }
}
