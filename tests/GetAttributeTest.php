<?php
namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use PDO;
use PDOException;

class GetAttributeTest
    extends
        EasyDBTest
{

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
