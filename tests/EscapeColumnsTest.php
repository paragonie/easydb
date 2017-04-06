<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use PHPUnit_Framework_TestCase;

class EscapeColumnsTest extends PHPUnit_Framework_TestCase
{
    public function dataEscapeColumns(): array
    {
        return [
            // [column, ...], quote, [engine => expected, ...]
            'with quoting, without alias' => [
                [
                    'id',
                    'username',
                ],
                true,
                [
                    'mysql' => ['`id`', '`username`'],
                    'mssql' => ['[id]', '[username]'],
                    '*' => ['"id"', '"username"'],
                ],
            ],
            'with quoting, with alias' => [
                [
                    'id' => 'user_id',
                    'username',
                ],
                true,
                [
                    'mysql' => ['`id` AS `user_id`', '`username`'],
                    'mssql' => ['[id] AS [user_id]', '[username]'],
                    '*' => ['"id" AS "user_id"', '"username"'],
                ],
            ],
            'without quoting, without alias' => [
                [
                    'id',
                    'username',
                ],
                false,
                [
                    '*' => ['id', 'username'],
                ],
            ],
            'without quoting, with alias' => [
                [
                    'id' => 'user_id',
                    'username',
                ],
                false,
                [
                    '*' => ['id AS user_id', 'username'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataEscapeColumns
     */
    public function testEscapeColumns(array $columns, $useQuotes, array $expectations)
    {
        foreach ($expectations as $engine => $expected) {
            $db = $this->getEasyDB($engine);

            $output = $db->escapeColumns($columns, $useQuotes);
            $this->assertSame($expected, $output, "Using $engine engine");
        }
    }

    private function getEasyDB(string $engine): EasyDB
    {
        $pdo = $this->createMock(\PDO::class);
        $db = new EasyDB($pdo, $engine);
        return $db;
    }
}
