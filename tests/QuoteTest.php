<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Class ExecTest
 * @package ParagonIE\EasyDB\Tests
 */
#[CoversClass(EasyDB::class)]
#[CoversClass(Factory::class)]
class QuoteTest extends EasyDBTestCase
{

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBQuoteProvider
     * @param callable $cb
     * @param $quoteThis
     * @param array $expectOneOfThese
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBQuoteProvider")]
    public function testQuote(callable $cb, $quoteThis, array $expectOneOfThese): void
    {
        $db = $this->easyDBExpectedFromCallable($cb);

        $this->assertTrue(count($expectOneOfThese) > 0);

        $matchedOneOfThose = false;
        $quoted = $db->quote((string)$quoteThis);

        foreach ($expectOneOfThese as $expectThis) {
            if ($quoted === $expectThis) {
                $this->assertSame($quoted, $expectThis);
                $matchedOneOfThose = true;
            }
        }
        if (!$matchedOneOfThose) {
            $this->assertTrue(
                false,
                'Did not match ' . $quoted . ' against any of ' . implode('; ', $expectOneOfThese)
            );
        }
    }
}
