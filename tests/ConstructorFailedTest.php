<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Exception\ConstructorFailed;
use ParagonIE\EasyDB\Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(EasyDB::class)]
#[CoversClass(Factory::class)]
#[CoversClass(ConstructorFailed::class)]
class ConstructorFailedTest extends EasyDBTestCase
{

    /**
     * @dataProvider badFactoryCreateArgumentProvider
     * @param $dsn
     * @param null $username
     * @param null $password
     * @param array $options
     */
    #[DataProvider("badFactoryCreateArgumentProvider")]
    public function testConstructorFailed($dsn, $username = null, $password = null, array $options = []): void
    {
        $this->expectException(ConstructorFailed::class);
        Factory::create($dsn, $username, $password, $options);
    }
}
