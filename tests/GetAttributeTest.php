<?php
namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use PDO;
use PDOException;
use ReflectionClass;

class GetAttributeTest
    extends
        EasyDBTest
{

    /**
    * EasyDB data provider
    * Returns an array of callables that return instances of EasyDB
    * @return array
    * @see EasyDBTest::GoodFactoryCreateArgument2EasyDBProvider()
    */
    public function GoodFactoryCreateArgument2EasyDBWithPDOAttributeProvider()
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
            $this->GoodFactoryCreateArgument2EasyDBProvider(),
            function (array $was, callable $cb) use ($attrs) {
                foreach ($attrs as $attr) {
                    $was[] = [
                        $cb,
                        $attr
                    ];
                }
                return $was;
            },
            []
        );
    }

    /**
    * @dataProvider GoodFactoryCreateArgument2EasyDBWithPDOAttributeProvider
    */
    public function testGetDriver(callable $cb, $attr)
    {
        $db = $cb();
        $this->assertInstanceOf(EasyDB::class, $db);
        $this->assertInstanceOf(PDO::class, $db->getPdo());
        try {
            $this->assertEquals(
                $db->getAttribute($attr),
                $db->getPdo()->getAttribute($attr)
            );
        } catch (PDOException $e) {
            if (
                strpos(
                    $e->getMessage(),
                    ': Driver does not support this function: driver does not support that attribute'
                ) !== false
            ) {
                $this->markTestSkipped($e->getMessage());
            } else {
                throw $e;
            }
        }
    }
}
