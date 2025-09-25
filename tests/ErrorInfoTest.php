<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Class ErrorInfoTest
 * @package ParagonIE\EasyDB\Tests
 */
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
