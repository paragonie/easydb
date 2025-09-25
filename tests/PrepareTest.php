<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Exception\QueryError;
use PDOStatement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Class ExecTest
 * @package ParagonIE\EasyDB\Tests
 */
#[CoversClass(EasyDB::class)]
class PrepareTest extends EasyDBWriteTestCase
{
    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBInsertManyProvider
     * @param callable $cb
     * @param array $maps
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBInsertManyProvider")]
    public function testQuery(callable $cb, array $maps): void
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
