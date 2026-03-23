<?php
declare(strict_types=1);
namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyStatement;
use ParagonIE\EasyDB\Exception\EasyDBException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests that kill trivial escaped mutants surfaced by mutation testing.
 */
#[CoversClass(EasyStatement::class)]
class TrivialMutantTest extends TestCase
{
    public function testExceptionConstructor(): void
    {
        $ex = new EasyDBException();
        $this->assertSame('', $ex->getMessage());
        $this->assertSame(0, $ex->getCode());
        $this->assertNull($ex->getPrevious());
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
}
