<?php

namespace ParagonIE\EasyDB\Tests;

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

    private function assertSql(EasyStatement $statement, $expected)
    {
        $this->assertSame($expected, $statement->sql());
        $this->assertSame($expected, (string) $statement);
    }

    private function assertValues(EasyStatement $statement, array $values)
    {
        $this->assertSame($values, $statement->values());
    }

    public function testEmpty()
    {
        $stmt = EasyStatement::open();
        $this->assertSql($stmt, '1');
    }

    public function testPrecedence()
    {
        $sth1 = EasyStatement::open();
        $sth1->with("a=1");
        $sth1->orWith("a=2");
        $sth1->orWith("a=3");

        $sth2 = EasyStatement::open();
        $sth2->with("status=1");
        $sth2->andWith($sth1);

        $this->assertSql($sth2, 'status=1 AND (a=1 OR a=2 OR a=3)');
    }
}
