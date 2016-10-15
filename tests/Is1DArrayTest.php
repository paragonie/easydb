<?php
namespace ParagonIE\EasyDB;

use PHPUnit_Framework_TestCase;

class Is1DArrayTest
    extends
        PHPUnit_Framework_TestCase
{


    public function EasyDBProvider()
    {
        return [
            [
                Factory::create('sqlite::memory:')
            ],
        ];
    }

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
