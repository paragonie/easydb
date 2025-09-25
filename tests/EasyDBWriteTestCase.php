<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use Throwable;
use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;

/**
 * Class EasyDBTestCase
 * @package ParagonIE\EasyDB\Tests
 */
#[CoversClass(EasyDB::class)]
abstract class EasyDBWriteTestCase extends EasyDBTestCase
{

    /**
    * EasyDB data provider
    * Returns an array of callables that return instances of EasyDB
    * @return array
    *
    * @psalm-return array<int, array{0:callable():EasyDB}>
    *
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
                    function () use ($dsn, $username, $password, $options) : EasyDB {
                        $factory = Factory::create(
                            $dsn,
                            $username,
                            $password,
                            $options
                        );
                        try {
                            $factory->run(
                                'CREATE TABLE irrelevant_but_valid_tablename (foo char(36) PRIMARY KEY)'
                            );
                            $factory->run(
                                'CREATE TABLE table_with_bool (foo char(36) PRIMARY KEY, bar BOOLEAN)'
                            );
                        } catch (Throwable $e) {
                            $this->markTestSkipped($e->getMessage());
                        }
                        return $factory;
                    }
                ];
            },
            static::goodFactoryCreateArgumentProvider()
        );
    }

    /**
    * Remaps EasyDBWriteTestCase::goodFactoryCreateArgument2EasyDBProvider()
    */
    public static function goodFactoryCreateArgument2EasyDBInsertManyProvider(): array
    {
        $cbArgsSets = static::goodFactoryCreateArgument2EasyDBProvider();
        $args = [
            [
                [
                    ['foo' => '1'],
                    ['foo' => '2'],
                    ['foo' => '3'],
                ],
            ],
        ];

        return \array_reduce(
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
}
