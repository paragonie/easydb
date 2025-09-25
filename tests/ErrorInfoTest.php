<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Class ErrorInfoTest
 * @package ParagonIE\EasyDB\Tests
 */
#[CoversClass(EasyDB::class)]
#[CoversClass(Factory::class)]
class ErrorInfoTest extends EasyDBTestCase
{

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBProvider")]
    public function testNoError(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);

        $info = $db->errorInfo();
        $this->assertIsArray($info);
        $this->assertSame($info[0], '00000');
        $this->assertSame($info[1], null);
        $this->assertSame($info[2], null);
    }
}
