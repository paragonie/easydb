<?php
namespace ParagonIE\EasyDB;

use PHPUnit_Framework_TestCase;

require_once('../vendor/autoload.php');

class unit
    extends
        PHPUnit_Framework_TestCase
{


    public function EasyDBProvider() : array
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
    }
}
