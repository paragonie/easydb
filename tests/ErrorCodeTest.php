<?php

namespace ParagonIE\EasyDB\Tests;

/**
 * Class EasyDBTest
 * @package ParagonIE\EasyDB\Tests
 */
class ErrorCodeTest extends EasyDBTest
{
    /**
     * @param callable $cb
     * @dataProvider goodFactoryCreateArgument2EasyDBProvider
     */
    public function testNoError(callable $cb)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $this->assertSame($db->errorCode(), '00000');
    }
}