<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use Exception;
use ParagonIE\EasyDB\EasyDB;
use PDO;
use PDOException;

class SetAttributeTest extends GetAttributeTest
{

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBWithPDOAttributeProvider
     * @depends      ParagonIE\EasyDB\Tests\GetAttributeTest::testAttribute
     * @param callable $cb
     * @param $attr
     * @param string $attrName
     * @throws Exception
     */
    public function testAttribute(callable $cb, $attr, string $attrName)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $skipping = [
            'ATTR_STATEMENT_CLASS'
        ];
        if (in_array($attrName, $skipping)) {
            $this->markTestSkipped(
                'Skipping tests for ' .
                EasyDB::class .
                '::setAttribute() with ' .
                PDO::class .
                '::' .
                $attrName .
                ' as provider for ' .
                static::class .
                '::' .
                __METHOD__ .
                '() currently does not provide values'
            );
        }
        try {
            $initial = $db->getAttribute($attr);
            $this->assertSame(
                $db->getAttribute($attr),
                $db->getPdo()->getAttribute($attr)
            );
            $this->assertSame(
                $db->setAttribute($attr, $db->getAttribute($attr)),
                $db->getPdo()->setAttribute($attr, $db->getAttribute($attr))
            );
            $this->assertSame(
                $db->setAttribute($attr, $db->getPdo()->getAttribute($attr)),
                $db->getPdo()->setAttribute($attr, $db->getPdo()->getAttribute($attr))
            );
            $this->assertSame(
                $db->getAttribute($attr),
                $initial
            );
            $this->assertSame(
                $db->getPdo()->getAttribute($attr),
                $initial
            );
        } catch (PDOException $e) {
            if (strpos(
                $e->getMessage(),
                ': Driver does not support this function: driver does not support that attribute'
            ) !== false
            ) {
                $this->markTestSkipped(
                    'Skipping tests for ' .
                    EasyDB::class .
                    '::setAttribute() with ' .
                    PDO::class .
                    '::' .
                    $attrName .
                    ' as driver "' .
                    $db->getDriver() .
                    '" does not support that attribute'
                );
            } else {
                throw $e;
            }
        } catch (Exception $e) {
            if ((
                    $attrName === 'ATTR_ERRMODE' &&
                    $e->getMessage() === 'EasyDB only allows the safest-by-default error mode (exceptions).'
                ) ||
                (
                    $attrName === 'ATTR_EMULATE_PREPARES' &&
                    $e->getMessage() === (
                        'EasyDB does not allow the use of emulated prepared statements' .
                        ', which would be a security downgrade.'
                    )
                )
            ) {
                $this->markTestSkipped(
                    'Skipping tests for ' .
                    EasyDB::class .
                    '::setAttribute() with ' .
                    PDO::class .
                    '::' .
                    $attrName .
                    ' as ' .
                    $e->getMessage()
                );
            } else {
                throw $e;
            }
        }
    }

    /**
    * EasyDB data provider
    * Returns an array of callables that return instances of EasyDB
    * @return array
    * @see EasyDBTest::goodFactoryCreateArgument2EasyDBProvider()
    */
    public function goodFactoryCreateArgument2EasyDBForSetPDOAttributeThrowsExceptionProvider()
    {
        $exceptionProvider = [
            [
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_SILENT,
                Exception::class,
                'EasyDB only allows the safest-by-default error mode (exceptions).',
            ],
            [
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_WARNING,
                Exception::class,
                'EasyDB only allows the safest-by-default error mode (exceptions).',
            ],
            [
                PDO::ATTR_EMULATE_PREPARES,
                true,
                Exception::class,
                'EasyDB does not allow the use of emulated prepared statements, which would be a security downgrade.',
            ],
            [
                PDO::ATTR_EMULATE_PREPARES,
                1,
                Exception::class,
                'EasyDB does not allow the use of emulated prepared statements, which would be a security downgrade.',
            ],
        ];
        return array_reduce(
            $this->goodFactoryCreateArgument2EasyDBProvider(),
            function (array $was, array $cbArgs) use ($exceptionProvider) {
                return array_merge(
                    $was,
                    array_map(
                        function (array $args) use ($cbArgs) {
                            foreach (array_reverse($cbArgs) as $cbArg) {
                                array_unshift($args, $cbArg);
                            }
                            return $args;
                        },
                        $exceptionProvider
                    )
                );
            },
            []
        );
    }

    /**
     * Test which attributes will always throw an exception when set
     * @dataProvider goodFactoryCreateArgument2EasyDBForSetPDOAttributeThrowsExceptionProvider
     * @depends      testAttribute
     * @param callable $cb
     * @param int $attribute
     * @param $value
     * @param string $exceptionClassName
     * @param string $exceptionMessage
     */
    public function testSetAttributeThrowsException(
        callable $cb,
        int $attribute,
        $value,
        string $exceptionClassName,
        string $exceptionMessage
    ) {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->expectException($exceptionClassName);
        $this->expectExceptionMessage($exceptionMessage);

        $db->setAttribute($attribute, $value);
    }
}
