<?php
declare (strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use PDO;
use PDOException;
use ReflectionClass;

class SetAttributeTest
    extends
        GetAttributeTest
{

    /**
    * @dataProvider GoodFactoryCreateArgument2EasyDBWithPDOAttributeProvider
    * @depends ParagonIE\EasyDB\Tests\GetAttributeTest::testAttribute
    */
    public function testAttribute(callable $cb, $attr)
    {
        $db = $this->EasyDBExpectedFromCallable($cb);
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
                $this->markTestSkipped($e->getMessage());
            } else {
                throw $e;
            }
        }
    }
}
