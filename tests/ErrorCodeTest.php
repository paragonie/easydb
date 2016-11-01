<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

/**
 * Class EasyDBTest
 * @package ParagonIE\EasyDB\Tests
 */
class ErrorCodeTest extends EasyDBTest
{

    /**
     * @param callable $cb
     * @dataProvider GoodFactoryCreateArgument2EasyDBProvider
     */
    public function testNoError(callable $cb)
    {
        $db = $this->EasyDBExpectedFromCallable($cb);

        $this->assertSame($db->errorCode(), '00000');
    }
}
