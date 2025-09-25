<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(EasyDB::class)]
class EmulatePreparesDisabledTest extends EasyDBTestCase
{

    /**
     * @dataProvider goodFactoryCreateArgumentProvider
     * @param $dsn
     * @param null $username
     * @param null $password
     * @param array $options
     */
    #[DataProvider("goodFactoryCreateArgumentProvider")]
    public function testEmulatePreparesDisabled(
        $expectedDriver,
        $dsn,
        $username = null,
        $password = null,
        array $options = []
    ) {
        $db = Factory::create($dsn, $username, $password, $options);
        $recheckWithForcedFalse = false;
        try {
            $this->assertFalse($db->getPdo()->getAttribute(PDO::ATTR_EMULATE_PREPARES));
            $recheckWithForcedFalse = true;
        } catch (PDOException $e) {
            $this->assertStringEndsWith(
                ': Driver does not support this function: driver does not support that attribute',
                $e->getMessage()
            );
        }

        $options[PDO::ATTR_EMULATE_PREPARES] = true;
        $db = Factory::create($dsn, $username, $password, $options);
        try {
            $this->assertFalse($db->getPdo()->getAttribute(PDO::ATTR_EMULATE_PREPARES));
        } catch (PDOException $e) {
            $this->assertStringEndsWith(
                ': Driver does not support this function: driver does not support that attribute',
                $e->getMessage()
            );
        }

        if ($recheckWithForcedFalse) {
            $options[PDO::ATTR_EMULATE_PREPARES] = false;
            $db = Factory::create($dsn, $username, $password, $options);
            $this->assertFalse($db->getPdo()->getAttribute(PDO::ATTR_EMULATE_PREPARES));
        }
    }
}
