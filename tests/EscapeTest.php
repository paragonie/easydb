<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\Exception\IdentifierException;
use PHPUnit_Framework_TestCase;

use function ParagonIE\EasyDB\escape_expression;
use function ParagonIE\EasyDB\escape_identifier;
use function ParagonIE\EasyDB\escape_identifier_unqualified;
use function ParagonIE\EasyDB\is_valid_identifier;
use function ParagonIE\EasyDB\assert_valid_identifier;

class EscapeTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataValid
     */
    public function testValid(string $identifier, bool $allow_qualified, bool $expected)
    {
        $this->assertSame($expected, is_valid_identifier($identifier, $allow_qualified));
    }

    public function dataValid()
    {
        return [
            'invalid, starts with a digit' => ['0col', false, false],
            'invalid, contains special characters' => ['bad!', false, false],
            'invalid, contains delimiter' => ['bad.reference', false, false],
            'invalid, qualified not allowed' => ['table.column', false, false],
            'invalid, too many parts' => ['a.b.c', true, false],
            'valid, all lowercase' => ['column', false, true],
            'valid, all uppercase' => ['TABLE', false, true],
            'valid, contains underscores' => ['user_id', false, true],
            'valid, contains digits' => ['is18', false, true],
            'valid, qualified' => ['table.column', true, true],
        ];
    }

    /**
     * @dataProvider dataAssertValid
     */
    public function testAssertValid(string $identifier)
    {
        $this->expectException(IdentifierException::class);
        $this->expectExceptionCode(IdentifierException::INVALID_IDENTIFIER);

        assert_valid_identifier($identifier);
    }

    public function dataAssertValid()
    {
        return [
            'cannot start with a digit' => ['0col'],
            'can only contain letters and digits' => ['bad!'],
            'qualified not allowed by default' => ['table.column'],
        ];
    }

    /**
     * @dataProvider dataUnqualified
     */
    public function testUnqualified(string $engine = null, string $identifier, string $expected)
    {
        $this->assertSame($expected, escape_identifier_unqualified($identifier, $engine));
    }

    public function dataUnqualified()
    {
        return [
            // engine, identifier, expected
            'pgsql wildcard' => ['pgsql', '*', '*'],
            'mssql wildcard' => ['mssql', '*', '*'],
            'mysql wildcard' => ['mysql', '*', '*'],
            'undefined wildcard' => [null, '*', '*'],

            'pgsql ident' => ['pgsql', 'col', '"col"'],
            'mssql ident' => ['mssql', 'col', '[col]'],
            'mysql ident' => ['mysql', 'col', '`col`'],
            'undefined ident' => [null, 'col', '"col"'],

            'pgsql single letter' => ['pgsql', 'a', '"a"'],
            'mssql single letter' => ['mssql', 'a', '[a]'],
            'mysql single letter' => ['mysql', 'a', '`a`'],
            'undefined single letter' => [null, 'a', '"a"'],

            'pgsql all caps' => ['pgsql', 'TABLE', '"TABLE"'],
            'mssql all caps' => ['mssql', 'TABLE', '[TABLE]'],
            'mysql all caps' => ['mysql', 'TABLE', '`TABLE`'],
            'undefined all caps' => [null, 'TABLE', '"TABLE"'],
        ];
    }

    /**
     * @dataProvider dataQualified
     */
    public function testQualified(string $engine = null, string $identifier, string $expected)
    {
        $this->assertSame($expected, escape_identifier($identifier, $engine));
    }

    public function dataQualified()
    {
        return [
            // engine, identifier, expected
            'pgsql qualified' => ['pgsql', 'table.col', '"table"."col"'],
            'mssql qualified' => ['mssql', 'table.col', '[table].[col]'],
            'mysql qualified' => ['mysql', 'table.col', '`table`.`col`'],
            'undefined qualified' => [null, 'table.col', '"table"."col"'],

            'pgsql single letter' => ['pgsql', 't.c', '"t"."c"'],
            'mssql single letter' => ['mssql', 't.c', '[t].[c]'],
            'mysql single letter' => ['mysql', 't.c', '`t`.`c`'],
            'undefined single letter' => [null, 't.c', '"t"."c"'],

            'pgsql all caps' => ['pgsql', 'TABLE.COL', '"TABLE"."COL"'],
            'mssql all caps' => ['mssql', 'TABLE.COL', '[TABLE].[COL]'],
            'mysql all caps' => ['mysql', 'TABLE.COL', '`TABLE`.`COL`'],
            'undefined all caps' => [null, 'TABLE.COL', '"TABLE"."COL"'],
        ];
    }

    /**
     * @dataProvider dataQualifiedInvalid
     */
    public function testQualifiedError(string $identifier)
    {
        $this->expectException(IdentifierException::class);
        $this->expectExceptionCode(IdentifierException::TOO_MANY_PARTS);

        escape_identifier($identifier);
    }

    public function dataQualifiedInvalid()
    {
        return [
            'all lowercase, too many parts' => ['foo.bar.baz'],
            'all uppercase, too many parts' => ['A.B.C'],
        ];
    }

    /**
     * @dataProvider dataExpression
     */
    public function testExpression(string $expression, string $expected)
    {
        $this->assertSame($expected, escape_expression($expression));
    }

    public function dataExpression()
    {
        // This does not test specific engines because other tests completely
        // cover those cases already.
        return [
            // expression, expected
            'fn qualified' => ['COUNT(user.id)', 'COUNT("user"."id")'],
            'fn equals value' => ['COUNT(user.id) > 100', 'COUNT("user"."id") > 100'],
            'fn equals column' => ['COUNT(user.id) > table.col', 'COUNT("user"."id") > "table"."col"'],

            'column equals column' => ['table.col = other.col', '"table"."col" = "other"."col"'],
            'column equals placeholder' => ['table.col = ?', '"table"."col" = ?'],
            'column equals bound' => ['table.col = :value', '"table"."col" = :value'],
        ];
    }
}
