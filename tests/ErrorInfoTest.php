<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

/**
 * Class ErrorInfoTest
 * @package ParagonIE\EasyDB\Tests
 */
class ErrorInfoTest extends EasyDBTest
{

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     * @param callable $cb
     */
    public function testNoError(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);

        $info = $db->errorInfo();
        $this->assertTrue(is_array($info));
        $this->assertSame($info[0], '00000');
        $this->assertSame($info[1], null);
        $this->assertSame($info[2], null);
    }
}
