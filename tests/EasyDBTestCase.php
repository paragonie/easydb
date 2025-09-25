<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use PHPUnit\Framework\TestCase as PHPUnit_Framework_TestCase;

/**
 * Class EasyDBTestCase
 * @package ParagonIE\EasyDB\Tests
 */
abstract class EasyDBTestCase extends PHPUnit_Framework_TestCase
{

    /**
    * Data provider for arguments to be passed to Factory::create
    * These arguments will not result in a valid EasyDB instance
    * @return array
    */
    public static function badFactoryCreateArgumentProvider(): array
    {
        return [
            [
                'this-dsn-will-fail',
                'username',
                'putastrongpasswordhere'
            ],
        ];
    }

    /**
    * Data provider for arguments to be passed to Factory::create
    * These arguments will result in a valid EasyDB instance
    * @return array
    */
    public static function goodFactoryCreateArgumentProvider(): array
    {
        if (!getenv('DB')) {
            return [
                [
                    'sqlite',
                    'sqlite::memory:',
                    null,
                    null,
                    [],
                ],
            ];
        }
        static::markTestIncomplete(
            'Could not determine appropriate arguments for ' .
            Factory::class .
            '::create() from getenv()'
        );
    }

    /**
    * EasyDB data provider
    * Returns an array of callables that return instances of EasyDB
    * @return array
    * @see EasyDBTestCase::goodFactoryCreateArgumentProvider()
    */
    public static function goodFactoryCreateArgument2EasyDBProvider(): array
    {
        return array_map(
            function (array $arguments) {
                $dsn = $arguments[1];
                $username = $arguments[2] ?? null;
                $password = $arguments[3] ?? null;
                $options = $arguments[4] ?? [];
                return [
                    function () use ($dsn, $username, $password, $options) {
                        return Factory::create(
                            $dsn,
                            $username,
                            $password,
                            $options
                        );
                    }
                ];
            },
            static::goodFactoryCreateArgumentProvider()
        );
    }

    /**
    * Strict-type paranoia for a callable that is expected to return an EasyDB instance
    * @param callable $cb
    * @return EasyDB
    */
    protected function easyDBExpectedFromCallable(callable $cb): EasyDB
    {
        return $cb();
    }

    /**
    * Remaps EasyDBWriteTest::goodFactoryCreateArgument2EasyDBProvider()
    */
    public static function goodFactoryCreateArgument2EasyDBQuoteProvider(): array
    {
        $cbArgsSets = static::goodFactoryCreateArgument2EasyDBProvider();
        $args = [
            [
                1,
                [
                    "'1'"
                ]
            ],
            [
                '1foo',
                [
                    "'1foo'"
                ]
            ]
        ];

        return array_reduce(
            $args,
            function (array $was, array $is) use ($cbArgsSets) {
                foreach ($cbArgsSets as $cbArgs) {
                    $args = array_values($is);
                    foreach (array_reverse($cbArgs) as $cbArg) {
                        array_unshift($args, $cbArg);
                    }
                    $was[] = $args;
                }

                return $was;
            },
            []
        );
    }

    public function assertEasydbRegExp($match, $str): void
    {
        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression($match, $str);
            return;
        }
        if (method_exists($this, 'assertRegExp')) {
            $this->assertRegExp($match, $str);
            return;
        }
        $this->assertIsInt(preg_match($match, $str));
    }
}
