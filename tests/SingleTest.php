<?php
namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use PDO;
use PDOException;

class SingleTest
    extends
        CellTest
{


    protected function getResultForMethod(EasyDB $db, $statement, $offset, $params)
    {
        return $db->single($statement, $params);
    }
}
