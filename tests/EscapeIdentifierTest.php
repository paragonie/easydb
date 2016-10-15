<?php
namespace ParagonIE\EasyDB\Tests;

use InvalidArgumentException;
use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use PDO;
use PDOException;

class EscapeIdentifierTest
    extends
        EasyDBTest
{

    /**
    * EasyDB data provider
    * Returns an array of callables that return instances of EasyDB
    * @return array
    * @see EasyDBTest::GoodFactoryCreateArgument2EasyDBProvider()
    */
    public function GoodFactoryCreateArgument2EasyDBWithIdentifierProvider()
    {
        $identifiers = [
            'foo',
            'foo1',
            'foo_2',
            'foo 3',
            'foo-4',
        ];
        return array_reduce(
            $this->GoodFactoryCreateArgument2EasyDBProvider(),
            function (array $was, callable $cb) use ($identifiers) {
                foreach ($identifiers as $identifier) {
                    $was[] = [
                        $cb,
                        $identifier
                    ];
                }
                return $was;
            },
            []
        );
    }

    /**
    * EasyDB data provider
    * Returns an array of callables that return instances of EasyDB
    * @return array
    * @see EasyDBTest::GoodFactoryCreateArgument2EasyDBProvider()
    */
    public function GoodFactoryCreateArgument2EasyDBWithBadIdentifierProvider()
    {
        $identifiers = [
            (string)1,
            '2foo',
            (string)false,
            (string)null,
            (string)[]
        ];
        return array_reduce(
            $this->GoodFactoryCreateArgument2EasyDBProvider(),
            function (array $was, callable $cb) use ($identifiers) {
                foreach ($identifiers as $identifier) {
                    $was[] = [
                        $cb,
                        $identifier
                    ];
                }
                return $was;
            },
            []
        );
    }

    private function getExpectedEscapedIdentifier($string, $driver, $quote)
    {
        $str = \preg_replace('/[^0-9a-zA-Z_]/', '', $string);

        if ($quote) {
            switch ($driver) {
                case 'mssql':
                    return '['.$str.']';
                case 'mysql':
                    return '`'.$str.'`';
                default:
                    return '"'.$str.'"';
            }
        }
        return $str;
    }

    /**
    * @dataProvider GoodFactoryCreateArgument2EasyDBWithIdentifierProvider
    */
    public function testEscapeIdentifier(callable $cb, $identifier)
    {
        $db = $cb();
        $this->assertInstanceOf(EasyDB::class, $db);
        $this->assertEquals(
            $db->escapeIdentifier($identifier, true),
            $this->getExpectedEscapedIdentifier($identifier, $db->getDriver(), true)
        );
        $this->assertEquals(
            $db->escapeIdentifier($identifier, false),
            $this->getExpectedEscapedIdentifier($identifier, $db->getDriver(), false)
        );
    }

    /**
    * @dataProvider GoodFactoryCreateArgument2EasyDBWithBadIdentifierProvider
    * @depends testEscapeIdentifier
    */
    public function testEscapeIdentifierThrowsException(callable $cb, $identifier)
    {
        $db = $cb();
        $this->assertInstanceOf(EasyDB::class, $db);
        $this->expectException(InvalidArgumentException::class);
        $db->escapeIdentifier($identifier);
    }


}
