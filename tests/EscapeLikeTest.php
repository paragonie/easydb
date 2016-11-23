<?php

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use PDO;
class EscapeLikeTest extends EasyDBTest
{
    public function dataValues()
    {
        return [['plain', 'plain'], ['%single', '\\%single'], ['%double%', '\\%double\\%'], ['_under_score_', '\\_under\\_score\\_'], ['%mix_ed', '\\%mix\\_ed'], ['\\%escaped?', '\\\\%escaped?']];
    }
    /**
     * @dataProvider dataValues
     */
    public function testEscapeLike($input, $expected)
    {
        // This defines sqlite, but mysql and postgres share the same rules
        $easydb = new EasyDB($this->getMockPDO(), 'sqlite');
        $output = $easydb->escapeLikeValue($input);
        $this->assertSame($expected, $output);
    }
    public function dataMSSQLValues()
    {
        return array_merge($this->dataValues(), [['[range]', '\\[range\\]'], ['[^negated]', '\\[^negated\\]']]);
    }
    /**
     * @dataProvider dataMSSQLValues
     */
    public function testMSSQLEscapeLike($input, $expected)
    {
        $easydb = new EasyDB($this->getMockPDO(), 'mssql');
        $output = $easydb->escapeLikeValue($input);
        $this->assertSame($expected, $output);
    }
    private function getMockPDO()
    {
        $mock = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();
        $mock->method('setAttribute')->willReturn(true);
        return $mock;
    }
}