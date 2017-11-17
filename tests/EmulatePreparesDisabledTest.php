<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\Factory;
use PDO;
use PDOException;

class EmulatePreparesDisabledTest extends EasyDBTest
{

    /**
     * @dataProvider goodFactoryCreateArgumentProvider
     * @param $dsn
     * @param null $username
     * @param null $password
     * @param array $options
     */
    public function testEmulatePreparesDisabled(
        $expectedDriver,
        $dsn,
        $username = null,
        $password = null,
        $options = []
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
