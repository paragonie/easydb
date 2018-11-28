<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\Exception\QueryError;
use PDOStatement;

/**
 * Class ExecTest
 * @package ParagonIE\EasyDB\Tests
 */
class PrepareTest extends EasyDBWriteTest
{

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBInsertManyProvider
     * @depends      ParagonIE\EasyDB\Tests\EscapeIdentifierTest::testEscapeIdentifier
     * @depends      ParagonIE\EasyDB\Tests\EscapeIdentifierTest::testEscapeIdentifierThrowsSomething
     * @param callable $cb
     * @param array $maps
     */
    public function testQuery(callable $cb, array $maps)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $table = 'irrelevant_but_valid_tablename';

        $first = $maps[0];

        // Let's make sure our keys are escaped.
        $keys = \array_keys($first);
        foreach ($keys as $i => $v) {
            $keys[$i] = $db->escapeIdentifier($v);
        }

        $count = \count($maps);
        for ($i = 0; $i < $count; ++$i) {
            $queryString = "INSERT INTO " . $db->escapeIdentifier($table) . " (";

            // Now let's append a list of our columns.
            $queryString .= \implode(', ', $keys);

            // This is the middle piece.
            $queryString .= ") VALUES (";

            // Now let's concatenate the ? placeholders
            $queryString .= \implode(
                ', ',
                \array_fill(0, \count($first), '?')
            );

            // Necessary to close the open ( above
            $queryString .= ");";

            $this->assertInstanceOf(PDOStatement::class, $db->prepare($queryString));
        }

        try {
            $db->prepare("\n");
            $this->fail("EasyDB::prepare() should be failing on empty queries.");
        } catch (QueryError $ex) {
        }
    }
}
