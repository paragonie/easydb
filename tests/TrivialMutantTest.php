<?php
declare(strict_types=1);
namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\EasyStatement;
use ParagonIE\EasyDB\Exception\EasyDBException;
use ParagonIE\EasyDB\Exception\ConstructorFailed;
use ParagonIE\EasyDB\Exception\MustBeNonEmpty;
use ParagonIE\EasyDB\Exception\MustBeOneDimensionalArray;
use ParagonIE\EasyDB\Factory;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
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

    public function testEasyDBEscapeValueSetEmpty(): void
    {
        $db = Factory::create('sqlite::memory:');
        $this->assertSame('(SELECT 1 WHERE FALSE)', $db->escapeValueSet([]));
    }

    public function testEasyDBColOffset(): void
    {
        $db = Factory::create('sqlite::memory:');
        $db->query("CREATE TABLE test_col (a int, b int, c int)");
        $db->insert('test_col', ['a' => 1, 'b' => 2, 'c' => 3]);
        $db->insert('test_col', ['a' => 4, 'b' => 5, 'c' => 6]);

        // test offset 1 -> b
        $result = $db->col("SELECT * FROM test_col", 1);
        $this->assertEquals([2, 5], $result);

        // test column with offset 2 -> c
        $result2 = $db->column("SELECT * FROM test_col", [], 2);
        $this->assertEquals([3, 6], $result2);
    }

    public function testEasyDbDDefaults(): void
    {
        $db = Factory::create('sqlite::memory:');
        $this->assertSame(false, $db->getAttribute(PDO::ATTR_EMULATE_PREPARES));
        $this->assertSame(PDO::ERRMODE_EXCEPTION, $db->getAttribute(PDO::ATTR_ERRMODE));
    }
}
