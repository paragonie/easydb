<?php
declare (strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;

class InTransactionTest
    extends
        EasyDBTest
{

    /**
    * @dataProvider GoodFactoryCreateArgumentProvider
    */
    public function testInTransaction($dsn, $username=null, $password=null, $options = array(), $expectedDriver)
    {
        $db = Factory::create($dsn, $username, $password, $options);
        $this->assertFalse($db->inTransaction());
        $db->beginTransaction();
        $this->assertTrue($db->inTransaction());
        $db->commit();
        $this->assertFalse($db->inTransaction());
        $db->beginTransaction();
        $this->assertTrue($db->inTransaction());
        $db->rollback();
        $this->assertFalse($db->inTransaction());
    }
}
