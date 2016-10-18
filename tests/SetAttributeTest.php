<?php
declare (strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use PDO;
use PDOException;

class SetAttributeTest
    extends
        GetAttributeTest
{

    /**
    * @dataProvider GoodFactoryCreateArgument2EasyDBWithPDOAttributeProvider
    * @depends ParagonIE\EasyDB\Tests\GetAttributeTest::testAttribute
    */
    public function testAttribute(callable $cb, $attr, string $attrName)
    {
        $db = $this->EasyDBExpectedFromCallable($cb);
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
            if (
                strpos(
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
        }
    }
}
