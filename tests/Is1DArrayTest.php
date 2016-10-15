<?php
namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;

class Is1DArrayTest
    extends
        EasyDBTest
{

    /**
    * @dataProvider EasyDBProvider
    */
    public function testIs1DArray(EasyDB $db)
    {
        $this->assertTrue($db->is1DArray([]));
        $this->assertFalse($db->is1DArray([[]]));
        $this->assertFalse($db->is1DArray([[],[]]));
        $this->assertTrue($db->is1DArray([1]));
        $this->assertFalse($db->is1DArray([[1]]));
        $this->assertFalse($db->is1DArray([[1],[2]]));
    }
}
