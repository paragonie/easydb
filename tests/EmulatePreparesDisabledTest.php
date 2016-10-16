<?php
namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use PDO;
use PDOException;

class EmulatePreparesDisabledTest
    extends
        EasyDBTest
{

    /**
    * @dataProvider GoodFactoryCreateArgumentProvider
    */
    public function testEmulatePreparesDisabled($dsn, $username=null, $password=null, $options = array())
    {
        $db = Factory::create($dsn, $username, $password, $options);
        $this->assertInstanceOf(EasyDB::class, $db);
        $this->assertInstanceOf(PDO::class, $db->getPDO());
        $recheckWithForcedFalse = false;
        try {
            $this->assertFalse($db->getPDO()->getAttribute(PDO::ATTR_EMULATE_PREPARES));
            $recheckWithForcedFalse = true;
        } catch (PDOException $e) {
            $this->assertStringEndsWith(
                ': Driver does not support this function: driver does not support that attribute',
                $e->getMessage()
            );
        }

        $options[PDO::ATTR_EMULATE_PREPARES] = true;
        $db = Factory::create($dsn, $username, $password, $options);
        $this->assertInstanceOf(EasyDB::class, $db);
        $this->assertInstanceOf(PDO::class, $db->getPDO());
        try {
            $this->assertFalse($db->getPDO()->getAttribute(PDO::ATTR_EMULATE_PREPARES));
        } catch (PDOException $e) {
            $this->assertStringEndsWith(
                ': Driver does not support this function: driver does not support that attribute',
                $e->getMessage()
            );
        }

        if ($recheckWithForcedFalse) {
            $options[PDO::ATTR_EMULATE_PREPARES] = false;
            $db = Factory::create($dsn, $username, $password, $options);
            $this->assertInstanceOf(EasyDB::class, $db);
            $this->assertInstanceOf(PDO::class, $db->getPDO());
            $this->assertFalse($db->getPDO()->getAttribute(PDO::ATTR_EMULATE_PREPARES));
        }
    }
}
