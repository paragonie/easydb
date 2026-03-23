<?php
declare(strict_types=1);
namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\EasyStatement;
use ParagonIE\EasyDB\Exception\EasyDBException;
use ParagonIE\EasyDB\Exception\ConstructorFailed;
use ParagonIE\EasyDB\Exception\InvalidIdentifier;
use ParagonIE\EasyDB\Exception\MustBeNonEmpty;
use ParagonIE\EasyDB\Exception\MustBeOneDimensionalArray;
use ParagonIE\EasyDB\Factory;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * Tests that kill trivial escaped mutants surfaced by mutation testing.
 */
#[CoversClass(EasyStatement::class)]
#[CoversClass(MustBeNonEmpty::class)]
#[CoversClass(MustBeOneDimensionalArray::class)]
#[CoversClass(ConstructorFailed::class)]
#[CoversClass(Factory::class)]
#[CoversClass(EasyDB::class)]
class TrivialMutantTest extends TestCase
{
    public function testExceptionConstructor(): void
    {
        $ex = new EasyDBException();
        $this->assertSame('', $ex->getMessage());
        $this->assertSame(0, $ex->getCode());
        $this->assertNull($ex->getPrevious());

        $ex2 = new MustBeNonEmpty();
        $this->assertSame('', $ex2->getMessage());
        $this->assertSame(0, $ex2->getCode());
        $this->assertNull($ex2->getPrevious());

        $ex3 = new MustBeOneDimensionalArray();
        $this->assertSame('', $ex3->getMessage());
        $this->assertSame(0, $ex3->getCode());
        $this->assertNull($ex3->getPrevious());
    }

    public function testEasyStatementPublic(): void
    {
        $st1 = EasyStatement::open()->with('tos_agreement IS NOT NULL');
        $st2 = EasyStatement::open()->with('last_login IS NOT NULL');
        $st1->andGroup()->with($st2);
        $st3 = $st1->andIn('groups IN (?*)', ['1', '2', '3', '6', '8']);
        $this->assertSame(
            'tos_agreement IS NOT NULL AND ((last_login IS NOT NULL)) AND groups IN (?, ?, ?, ?, ?)',
            $st3->sql()
        );
        $this->assertSame(['1', '2', '3', '6', '8'], $st3->values());

        $st4 = EasyStatement::open()
            ->with('policy IS NOT NULL')
            ->andWithString('foo = ?', 'bar');
        $this->assertSame('policy IS NOT NULL AND foo = ?', $st4->sql());
        $this->assertSame(['bar'], $st4->values());

        $st4->orWithString('baz = ?', 'qux');
        $this->assertSame('policy IS NOT NULL AND foo = ? OR baz = ?', $st4->sql());
        $this->assertSame(['bar', 'qux'], $st4->values());
    }

    public function testFactoryFromArrayInvalidArgIndices(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessageMatches('/Argument #\d+ \(\$\w+\) must be of type string/');
        // $dsn requires string, we pass array
        Factory::fromArray([['array']]);
    }

    public function testFactoryMissingDriver(): void
    {
        $this->expectException(ConstructorFailed::class);
        $this->expectExceptionMessage('Could not create a PDO connection. Is the driver installed/enabled?');
        Factory::create('fake_driver_that_does_not_exist:host=localhost');
    }

    public function testFactoryBadCredentials(): void
    {
        if (!extension_loaded('pdo_mysql')) {
            $this->markTestSkipped('pdo_mysql is not available');
        }
        $this->expectException(ConstructorFailed::class);
        $this->expectExceptionMessage('Could not create a PDO connection. Please check your username and password.');
        Factory::create(
            'mysql:host=127.0.0.1;dbname=database_that_absolutely_does_not_exist',
            'baduser',
            'badpass'
        );
    }

    public function testFactoryFromArrayOptions(): void
    {
        $this->expectException(ConstructorFailed::class);
        Factory::fromArray(['mysql:host=127.0.0.1', 123, 456, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]]);
    }

    public function testFactoryCreateWithoutColon(): void
    {
        $this->expectException(ConstructorFailed::class);
        Factory::create('invalid_dsn_format');
    }

    public function testFactoryCreateMysqlWithCharset(): void
    {
        if (!extension_loaded('pdo_mysql')) {
            $this->markTestSkipped('pdo_mysql is not available');
        }
        $this->expectException(ConstructorFailed::class);
        $this->expectExceptionMessage('Could not create a PDO connection. Please check your username and password.');
        // This will test the case where `;charset=` is already present, so the default isn't appended
        Factory::create('mysql:host=127.0.0.1;charset=latin1', 'baduser', 'badpass');
    }

    public function testEasyDBEscapeValueSetEmpty(): void
    {
        $db = Factory::create('sqlite::memory:');
        $this->assertSame('(SELECT 1 WHERE FALSE)', $db->escapeValueSet([]));
    }

    public function testEscapeValueSetExceptionsInt(): void
    {
        $db = Factory::create('sqlite::memory:');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a integer at index 0 of argument 1 passed to ParagonIE\EasyDB\EasyDB::ParagonIE\EasyDB\EasyDB::escapeValueSet(), received string');
        $db->escapeValueSet(['invalid'], 'int');
    }

    public function testEscapeValueSetExceptionsFloat(): void
    {
        $db = Factory::create('sqlite::memory:');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a number at index 1 of argument 1 passed to ParagonIE\EasyDB\EasyDB::ParagonIE\EasyDB\EasyDB::escapeValueSet(), received NULL');
        $db->escapeValueSet([1.0, null], 'float');
    }

    public function testEscapeValueSetExceptionsString(): void
    {
        $db = Factory::create('sqlite::memory:');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a string at index foo of argument 1 passed to ParagonIE\EasyDB\EasyDB::ParagonIE\EasyDB\EasyDB::escapeValueSet(), received NULL');
        $db->escapeValueSet(['foo' => null], 'string');
    }

    public function testEscapeIdentifierExceptionsDots(): void
    {
        $db = Factory::create('sqlite::memory:');
        $this->expectException(InvalidIdentifier::class);
        $this->expectExceptionMessage('Separators (.) are not permitted.');
        $db->escapeIdentifier('table.column');
    }

    public function testEscapeIdentifierExceptionsInvalidChars(): void
    {
        $db = Factory::create('sqlite::memory:');
        $this->expectException(InvalidIdentifier::class);
        $this->expectExceptionMessage('Invalid identifier: Invalid characters supplied.');
        $db->escapeIdentifier('table column!');
    }

    public function testEasyDBColOffset(): void
    {
        $db = Factory::create('sqlite::memory:');
        $db->query("CREATE TABLE test_col (a int, b int, c int)");
        $db->insert('test_col', ['a' => 1, 'b' => 2, 'c' => 3]);
        $db->insert('test_col', ['a' => 4, 'b' => 5, 'c' => 6]);

        // test default offset 0 -> a
        $result0 = $db->col("SELECT * FROM test_col");
        $this->assertEquals([1, 4], $result0);

        // test column with default offset 0 -> a
        $result0_col = $db->column("SELECT * FROM test_col");
        $this->assertEquals([1, 4], $result0_col);

        // test offset 1 -> b
        $result = $db->col("SELECT * FROM test_col", 1);
        $this->assertEquals([2, 5], $result);

        // test column with offset 2 -> c
        $result2 = $db->column("SELECT * FROM test_col", [], 2);
        $this->assertEquals([3, 6], $result2);
    }

    public static function easyDbDrivers(): array
    {
        $provided = [];

        //SQLITE
        if (extension_loaded('pdo_sqlite')) {
            $db = Factory::create(
                'sqlite:' . __DIR__ . '/mutate-kill.sql',
            );
            $db2 = new EasyDB(new PDO(
                'sqlite:' . __DIR__ . '/mutate-kill2.sql',
            ));
            $provided[] = [$db, $db2];
        }

        if (extension_loaded('pdo_pgsql') && getenv('PGSQL_HOST')) {
            $db = Factory::create(
                'pgsql:host=' . getenv('PGSQL_HOST') . ';dbname=' . getenv('PGSQL_DB'),
                getenv('PGSQL_USER'),
                getenv('PGSQL_PASS'),
            );
            $db2 = new EasyDB(new PDO(
                'pgsql:host=' . getenv('PGSQL_HOST') . ';dbname=' . getenv('PGSQL_DB'),
                getenv('PGSQL_USER'),
                getenv('PGSQL_PASS'),
            ));
            $provided[] = [$db, $db2];
        }

        if (extension_loaded('pdo_mysql') && getenv('MYSQL_HOST')) {
            $db = Factory::create(
                'mysql:host=' . getenv('MYSQL_HOST') . ';dbname=' . getenv('MYSQL_DB'),
                getenv('MYSQL_USER'),
                getenv('MYSQL_PASS'),
            );
            $db2 = new EasyDB(new PDO(
                'mysql:host=' . getenv('MYSQL_HOST') . ';dbname=' . getenv('MYSQL_DB'),
                getenv('MYSQL_USER'),
                getenv('MYSQL_PASS'),
            ));
            $provided[] = [$db, $db2];
        }

        return $provided;
    }

    /**
     * @dataProvider easyDbDrivers
     */
    #[DataProvider("easyDbDrivers")]
    public function testEasyDbDDefaults(EasyDB $db, EasyDB $db2): void
    {
        try {
            $this->assertSame(false, $db->getAttribute(PDO::ATTR_EMULATE_PREPARES));
            $this->assertSame(PDO::ERRMODE_EXCEPTION, $db->getAttribute(PDO::ATTR_ERRMODE));
            $this->assertSame(false, $db2->getAttribute(PDO::ATTR_EMULATE_PREPARES));
            $this->assertSame(PDO::ERRMODE_EXCEPTION, $db2->getAttribute(PDO::ATTR_ERRMODE));
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'driver does not support that attribute')) {
                $this->markTestSkipped('driver does not support that attribute');
            }
        }
    }
}
