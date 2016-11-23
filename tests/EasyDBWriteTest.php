<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use Exception;
use ParagonIE\EasyDB\Factory;

/**
 * Class EasyDBTest
 * @package ParagonIE\EasyDB\Tests
 */
abstract class EasyDBWriteTest extends
        EasyDBTest
{

    /**
    * EasyDB data provider
    * Returns an array of callables that return instances of EasyDB
    * @return array
    * @see EasyDBTest::GoodFactoryCreateArgumentProvider()
    */
    public function GoodFactoryCreateArgument2EasyDBProvider()
    {
        return array_map(
            function (array $arguments) {
                $dsn = $arguments[0];
                $username = isset($arguments[1]) ? $arguments[1] : null;
                $password = isset($arguments[2]) ? $arguments[2] : null;
                $options = isset($arguments[3]) ? $arguments[3] : [];
                return [
                    function () use ($dsn, $username, $password, $options) {
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
                        } catch (Exception $e) {
                            $this->markTestSkipped($e->getMessage());
                            return null;
                        }
                        return $factory;
                    }
                ];
            },
            $this->GoodFactoryCreateArgumentProvider()
        );
    }

    /**
    * Remaps EasyDBWriteTest::GoodFactoryCreateArgument2EasyDBProvider()
    */
    public function GoodFactoryCreateArgument2EasyDBInsertManyProvider()
    {
        $cbArgsSets = $this->GoodFactoryCreateArgument2EasyDBProvider();
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
