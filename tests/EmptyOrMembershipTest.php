<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\EasyStatement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use PDO;

#[CoversClass(EasyStatement::class)]
#[UsesClass(EasyDB::class)]
final class EmptyOrMembershipTest extends TestCase
{
    public function testEmptyMembershipGroupDoesNotGrantAllRows(): void
    {
        $db = new EasyDB(new PDO('sqlite::memory:'));
        $db->run('CREATE TABLE documents (id INTEGER, owner_id INTEGER)');
        $db->insertMany('documents', [['id' => 1, 'owner_id' => 7], ['id' => 2, 'owner_id' => 8]]);
        $where = EasyStatement::open()->setEmptyInStatementsAllowed(true)
            ->with('owner_id = ?', 7)->orGroup()->orIn('id IN (?*)', [])->endGroup();
        $this->assertSame(
            [1],
            array_map(
                'intval',
                $db->col('SELECT id FROM documents WHERE ' . $where->sql(), 0, ...$where->values())
            )
        );
    }
}
