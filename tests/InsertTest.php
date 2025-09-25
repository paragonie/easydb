<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use InvalidArgumentException;
use ParagonIE\EasyDB\Exception\InvalidIdentifier;
use ParagonIE\EasyDB\Exception\InvalidTableName;
use ParagonIE\EasyDB\Exception\MustBeOneDimensionalArray;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;

class InsertTest extends EasyDBWriteTest
{

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    public function testInsertNoFieldsThrowsException(callable $cb): void
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(PDOException::class);
        $this->assertNull($db->insert('irrelevant_but_valid_tablename', []));
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testInsertTableNameThrowsException(callable $cb): void
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(InvalidIdentifier::class);
        $db->insert('', ['foo' => 1]);
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testInsertMapArgThrowsException(callable $cb): void
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(MustBeOneDimensionalArray::class);
        $db->insert('irrelevant_but_valid_tablename', [[1]]);
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testInsertMapArgKeysThrowsException(callable $cb): void
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(InvalidIdentifier::class);
        $db->insert('irrelevant_but_valid_tablename', ['1foo' => 1]);
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testInsertIncorrectFieldThrowsException(callable $cb): void
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(PDOException::class);
        $db->insert('irrelevant_but_valid_tablename', ['bar' => 1]);
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testInsert(callable $cb): void
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $db->insert('irrelevant_but_valid_tablename', ['foo' => 1]);
        $this->assertEquals(
            $db->single('SELECT COUNT(foo) FROM irrelevant_but_valid_tablename WHERE foo = ?', [1]),
            '1'
        );
        $db->insert('table_with_bool', ['foo' => 'test', 'bar' => true]);
        $this->assertEquals(
            $db->single('SELECT COUNT(foo) FROM table_with_bool WHERE bar'),
            '1'
        );
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testBuildeInsertSql(callable $cb): void
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $statement = $db->buildInsertQuery('test_table', ['id', 'col1', 'col2']);
        $expected = '/insert into .test_table. \(.id., .col1., .col2.\) VALUES \(\?, \?, \?\)/i';
        $this->assertEasydbRegExp($expected, $statement);
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testBuildInsertIgnoreSql(callable $cb): void
    {
        $db = $this->easyDBExpectedFromCallable($cb);

        list($query) = $db->buildInsertQueryBoolSafe(
            'test_table',
            [
                'foo' => 'bar',
            ],
            false
        );

        $this->assertEasydbRegExp(
            '/insert ignore into .test_table. \(.foo.\) VALUES \(\?\)/i',
            $query
        );
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testBuildInsertOnDuplicateKeyUpdate(callable $cb): void
    {
        $db = $this->easyDBExpectedFromCallable($cb);

        list($query) = $db->buildInsertQueryBoolSafe(
            'test_table',
            [
                'foo' => 'bar',
            ],
            [
                'foo',
            ]
        );

        $this->assertEasydbRegExp(
            '/insert into .test_table. \(.foo.\) VALUES \(\?\) ON DUPLICATE KEY UPDATE .foo. = VALUES\(.foo.\)/i',
            $query
        );
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testBuildInsertOnDuplicateKeyUpdateMultiple(callable $cb): void
    {
        $db = $this->easyDBExpectedFromCallable($cb);

        list($query) = $db->buildInsertQueryBoolSafe(
            'test_table',
            [
                'foo' => 'bar',
                'bar' => 1,
                'baz' => 2,
            ],
            [
                'bar',
                'baz',
            ]
        );

        $this->assertEasydbRegExp(
            '/insert into .test_table. \(.foo., .bar., .baz.\) VALUES \(\?, \?, \?\) ON DUPLICATE KEY UPDATE .bar. = VALUES\(.bar.\), .baz. = VALUES\(.baz.\)/i',
            $query
        );
    }
}
