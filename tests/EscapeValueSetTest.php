<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use InvalidArgumentException;
use ParagonIE\EasyDB\Exception\MustBeOneDimensionalArray;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Class EasyDBTestCase
 * @package ParagonIE\EasyDB\Tests
 */
class EscapeValueSetTest extends EasyDBTestCase
{

    /**
    * Remaps EasyDBWriteTest::goodFactoryCreateArgument2EasyDBProvider()
    */
    public static function goodFactoryCreateArgument2EasyDBEscapeValueSetProvider(): array
    {
        $cbArgsSets = static::goodFactoryCreateArgument2EasyDBProvider();
        $args = [
            [
                [],
                'int',
                [
                    '(SELECT 1 WHERE FALSE)',
                ],
            ],
            [
                [],
                'float',
                [
                    '(SELECT 1 WHERE FALSE)',
                ],
            ],
            [
                [],
                'decimal',
                [
                    '(SELECT 1 WHERE FALSE)',
                ],
            ],
            [
                [],
                'number',
                [
                    '(SELECT 1 WHERE FALSE)',
                ],
            ],
            [
                [],
                'numeric',
                [
                    '(SELECT 1 WHERE FALSE)',
                ],
            ],
            [
                [],
                'string',
                [
                    '(SELECT 1 WHERE FALSE)',
                ],
            ],
            [
                [1, 2, 3, 5],
                '-this-does-not-exist-',
                [
                    '(SELECT 1 WHERE FALSE)',
                ]
            ],
            [
                [1, 2, 3, 5],
                'int',
                [
                    '(1, 2, 3, 5)',
                ]
            ],
            [
                [1, 2, 3, 5],
                'float',
                [
                    '(1, 2, 3, 5)',
                ]
            ],
            [
                [1, 2, 3, 5],
                'decimal',
                [
                    '(1, 2, 3, 5)',
                ]
            ],
            [
                [1, 2, 3, 5],
                'number',
                [
                    '(1, 2, 3, 5)',
                ]
            ],
            [
                [1, 2, 3, 5],
                'numeric',
                [
                    '(1, 2, 3, 5)',
                ]
            ],
            [
                [1, 2, 3, 5],
                'string',
                [
                    "('1', '2', '3', '5')",
                ]
            ],
        ];

        return array_reduce(
            $args,
            function (array $was, array $is) use ($cbArgsSets) {
                foreach ($cbArgsSets as $cbArgs) {
                    $args = array_values($is);
                    foreach (array_reverse($cbArgs) as $cbArg) {
                        array_unshift($args, $cbArg);
                    }
                    $was[] = $args;
                }

                return $was;
            },
            []
        );
    }

    /**
    * Remaps EasyDBWriteTest::goodFactoryCreateArgument2EasyDBProvider()
    */
    public static function badFactoryCreateArgument2EasyDBEscapeValueSetProvider(): array
    {
        $cbArgsSets = static::goodFactoryCreateArgument2EasyDBProvider();
        $buildArgs = [
            [
                [
                    'int',
                ],
                [
                    ['1', 2, 3, 5],
                    ['1foo', 2, 3, 5],
                    [null, 2, 3, 5],
                    [true, 2, 3, 5],
                    [false, 2, 3, 5],
                    [(new \stdClass), 2, 3, 5],
                ]
            ],
            [
                [
                    'string',
                ],
                [
                    [null, 2, 3, 5],
                    [true, 2, 3, 5],
                    [false, 2, 3, 5],
                    [(new \stdClass), 2, 3, 5],
                ]
            ],
            [
                [
                    'float',
                    'decimal',
                    'number',
                    'numeric',
                ],
                [
                    ['1foo', 2, 3, 5],
                    [null, 2, 3, 5],
                    [true, 2, 3, 5],
                    [false, 2, 3, 5],
                    [(new \stdClass), 2, 3, 5],
                ]
            ]
        ];
        $args = array_reduce(
            $buildArgs,
            function (array $was, array $is) {
                foreach ($is[0] as $type) {
                    $was = array_merge(
                        $was,
                        array_reduce(
                            $is[1],
                            function (array $innerWas, array $valueSet) use ($type) {
                                $innerWas[] = [
                                    $valueSet,
                                    $type
                                ];
                                return $innerWas;
                            },
                            []
                        )
                    );
                }
                return $was;
            },
            []
        );

        return array_reduce(
            $args,
            function (array $was, array $is) use ($cbArgsSets) {
                foreach ($cbArgsSets as $cbArgs) {
                    $args = array_values($is);
                    foreach (array_reverse($cbArgs) as $cbArg) {
                        array_unshift($args, $cbArg);
                    }
                    $was[] = $args;
                }

                return $was;
            },
            []
        );
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testEscapeValueSetFailsIs1DArray(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(MustBeOneDimensionalArray::class);
        $db->escapeValueSet([[1]]);
    }

    /**
     * @dataProvider badFactoryCreateArgument2EasyDBEscapeValueSetProvider
     * @param callable $cb
     * @param array $escapeThis
     * @param string $escapeThatAsType
     */
    #[DataProvider("badFactoryCreateArgument2EasyDBEscapeValueSetProvider")]
    public function testEscapeValueSetThrowsException(callable $cb, array $escapeThis, string $escapeThatAsType)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException(InvalidArgumentException::class);
        $db->escapeValueSet($escapeThis, $escapeThatAsType);
    }

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBEscapeValueSetProvider
     * @param callable $cb
     * @param array $escapeThis
     * @param string $escapeThatAsType
     * @param array $expectOneOfThese
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBEscapeValueSetProvider")]
    public function testEscapeValueSet(
        callable $cb,
        array $escapeThis,
        string $escapeThatAsType,
        array $expectOneOfThese
    ) {
        $db = $this->easyDBExpectedFromCallable($cb);

        $this->assertTrue(count($expectOneOfThese) > 0);

        $matchedOneOfThose = false;
        $quoted = $db->escapeValueSet($escapeThis, $escapeThatAsType);

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
