<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(EasyDB::class)]
class InTransactionTest extends EasyDBTestCase
{

    /**
     * @param $dsn
     * @param string|null $username
     * @param string|null $password
     * @param array $options
     * @dataProvider goodFactoryCreateArgumentProvider
     */
    #[DataProvider("goodFactoryCreateArgumentProvider")]
    public function testInTransaction(
        string $expectedDriver,
        string $dsn,
        ?string $username = null,
        ?string $password = null,
        array $options = []
    ): void {
        $db = Factory::create($dsn, $username, $password, $options);
        $this->assertFalse($db->inTransaction());
        $db->beginTransaction();
        $this->assertTrue($db->inTransaction());
        $db->commit();
        $this->assertFalse($db->inTransaction());
        $db->beginTransaction();
        $this->assertTrue($db->inTransaction());
        $db->rollBack();
        $this->assertFalse($db->inTransaction());
    }
}
