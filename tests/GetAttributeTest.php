<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use PDO;
use PDOException;
use ReflectionClass;

class GetAttributeTest extends EasyDBTest
{

    /**
    * EasyDB data provider
    * Returns an array of callables that return instances of EasyDB
    * @return array
    * @see EasyDBTest::goodFactoryCreateArgument2EasyDBProvider()
    */
    public function goodFactoryCreateArgument2EasyDBWithPDOAttributeProvider()
    {
        $ref = new ReflectionClass(PDO::class);
        if (defined('ARRAY_FILTER_USE_KEY')) {
            $attrs = array_filter(
                $ref->getConstants(),
                function ($attrName) {
                    return (strpos($attrName, 'ATTR_') === 0);
                },
                ARRAY_FILTER_USE_KEY
            );
        } else {
            $constants = $ref->getConstants();
            $attrs = array_reduce(
                array_keys($constants),
                function (array $was, $attrName) use ($constants) {
                    if (strpos($attrName, 'ATTR_') === 0) {
                        $was[$attrName] = $constants[$attrName];
                    }
                    return $was;
                },
                []
            );
        }
        return array_reduce(
            $this->goodFactoryCreateArgument2EasyDBProvider(),
            function (array $was, array $cbArgs) use ($attrs) {
                foreach ($attrs as $attrName => $attr) {
                    $args = [$attr, $attrName];
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
     * @dataProvider goodFactoryCreateArgument2EasyDBWithPDOAttributeProvider
     * @param callable $cb
     * @param $attr
     * @param string $attrName
     */
    public function testAttribute(callable $cb, $attr, string $attrName)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        try {
            $this->assertSame(
                $db->getAttribute($attr),
                $db->getPdo()->getAttribute($attr)
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
                    '::getAttribute(' .
                        PDO::class .
                        '::' .
                        $attrName .
                    '), as driver "' .
                    $db->getDriver() .
                    '" does not support that attribute'
                );
                $this->markTestSkipped($e->getMessage());
            } else {
                throw $e;
            }
        }
    }
}
