<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Class EasyDBTestCase
 * @package ParagonIE\EasyDB\Tests
 */
#[CoversClass(EasyDB::class)]
#[CoversClass(Factory::class)]
class ErrorCodeTest extends EasyDBTestCase
{

    /**
     * @param callable $cb
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testNoError(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);

        $this->assertSame($db->errorCode(), '00000');
    }
}
