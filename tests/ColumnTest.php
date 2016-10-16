<?php
namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use PDO;
use PDOException;

class ColumnTest
    extends
        ColTest
{


    protected function getResultForMethod(EasyDB $db, $statement, $offset, $params)
    {
        return $db->column($statement, $params, $offset);
    }
}
