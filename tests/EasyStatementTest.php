<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\EasyStatement;
use PHPUnit_Framework_TestCase as TestCase;
use RuntimeException;

/**
 * @package ParagonIE\EasyDB\Tests
 */
class EasyStatementTest extends TestCase
{
    public function testBasicAndOr()
    {
        $statement = EasyStatement::open()
            ->with('id = ?', 1)
            ->andWith('last_login > ?', 'today')
            ->orWith('last_login IS NULL');

        $this->assertSql($statement, 'id = ? AND last_login > ? OR last_login IS NULL');
        $this->assertValues($statement, [1, 'today']);
    }

    public function testLogicalIn()
    {
        $statement = EasyStatement::open()
            ->in('role_id IN (?*)', [1, 2, 3])
            ->orIn('user_id IN (?*)', [100]);

        $this->assertSql($statement, 'role_id IN (?, ?, ?) OR user_id IN (?)');
        $this->assertValues($statement, [1, 2, 3, 100]);

        $statement = EasyStatement::open()
            ->orIn('role_id IN (?*)', [4, 5, 6]);

        $this->assertSql($statement, 'role_id IN (?, ?, ?)');
        $this->assertValues($statement, [4, 5, 6]);
    }

    public function testGroupingWithAnd()
    {
        $statement = EasyStatement::open()
            ->with('id = ?', 1);

        $group = $statement->group()
            ->with('last_login > ?', 'today')
            ->orWith('last_login IS NULL');

        $this->assertSame($statement, $group->endGroup());

        $this->assertSql($group, 'last_login > ? OR last_login IS NULL');
        $this->assertSql($statement, 'id = ? AND (' . $group->sql() . ')');

        $this->assertValues($statement, [1, 'today']);
    }

    public function testGroupingWithOr()
    {
        $statement = EasyStatement::open()
            ->orGroup()
                ->with('failed_logins > ?', 5)
                ->andWith('last_login IS NULL')
            ->end()
            ->orGroup()
                ->with('role = ?', 'banned')
            ->end();

        $this->assertSql($statement, '(failed_logins > ? AND last_login IS NULL) OR (role = ?)');
        $this->assertValues($statement, [5, 'banned']);
    }

    public function testGroupParent()
    {
        $this->expectException(RuntimeException::class);

        EasyStatement::open()->endGroup();
    }

    public function testEscapeIdentifiers()
    {
        $s = EasyStatement::open()
            ->with('u.id = o.id')
            ->with('u.role = 3')
            ->with('u.name LIKE ?', 'test');

        $this->assertEscapedSql($s, 'mysql', '`u`.`id` = `o`.`id` AND `u`.`role` = 3 AND `u`.`name` LIKE ?');
        $this->assertEscapedSql($s, 'mssql', '[u].[id] = [o].[id] AND [u].[role] = 3 AND [u].[name] LIKE ?');
        $this->assertEscapedSql($s, 'pgsql', '"u"."id" = "o"."id" AND "u"."role" = 3 AND "u"."name" LIKE ?');
        $this->assertEscapedSql($s, 'sqlite', '"u"."id" = "o"."id" AND "u"."role" = 3 AND "u"."name" LIKE ?');
    }

    private function assertSql(EasyStatement $statement, $expected)
    {
        $this->assertSame($expected, $statement->sql());
        $this->assertSame($expected, (string) $statement);
    }

    private function assertEscapedSql(EasyStatement $statement, $engine, $expected)
    {
        $db = $this->getEasyDB($engine);
        $this->assertSame($expected, $statement->sql($db), "Escaped with $engine");
    }

    private function assertValues(EasyStatement $statement, array $values)
    {
        $this->assertSame($values, $statement->values());
    }

    private function getEasyDB($engine)
    {
        $pdo = $this->createMock(\PDO::class);
        $db = new EasyDB($pdo, $engine);
        $db->setAllowSeparators(true);
        return $db;
    }
}
