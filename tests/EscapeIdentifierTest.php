<?php
declare (strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use Throwable;

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
            1,
                '2foo',
            null,
            false,
            []
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
        $db = $this->EasyDBExpectedFromCallable($cb);
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
    public function testEscapeIdentifierThrowsSomething(callable $cb, $identifier)
    {
        $db = $this->EasyDBExpectedFromCallable($cb);
        $this->expectException(Throwable::class);
        $db->escapeIdentifier($identifier);
    }


}
