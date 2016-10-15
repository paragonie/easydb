<?php
namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use PDO;
use PDOException;

class SafeQueryTest
    extends
        RunTest
{


    protected function getResultForMethod(EasyDB $db, $statement, $offset, $params)
    {
        return $db->safeQuery($statement, $params);
    }
}
