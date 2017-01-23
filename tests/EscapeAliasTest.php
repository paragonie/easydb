<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use PHPUnit_Framework_TestCase;

class EscapeAliasTest extends PHPUnit_Framework_TestCase
{
    public function dataEscapeAlias(): array
    {
        return [
            // identifier, alias, quote, separators, [engine => expected, ...]
            'with quoting, without separator' => [
                'user', 'u', true, false, [
                    'mysql' => '`user` AS `u`',
                    'mssql' => '[user] AS [u]',
                    'pgsql' => '"user" AS "u"',
                    'sqlite' => '"user" AS "u"',
                ],
            ],
            'with quoting, with separator' => [
                'user.id', 'user_id', true, true, [
                    'mysql' => '`user`.`id` AS `user_id`',
                    'mssql' => '[user].[id] AS [user_id]',
                    'pgsql' => '"user"."id" AS "user_id"',
                    'sqlite' => '"user"."id" AS "user_id"',
                ],
            ],
            'without quoting, without separator' => [
                'user', 'u', false, false, [
                    '*' => 'user AS u',
                ],
            ],
            'without quoting, with separator' => [
                'user.id', 'user_id', false, true, [
                    '*' => 'user.id AS user_id',
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataEscapeAlias
     */
    public function testEscapeAlias($identifier, $alias, $useQuotes, $allowSeparators, array $expectations)
    {
        foreach ($expectations as $engine => $expected) {
            $db = $this->getEasyDB($engine);
            $db->setAllowSeparators($allowSeparators);

            $output = $db->escapeAlias($identifier, $alias, $useQuotes);
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
