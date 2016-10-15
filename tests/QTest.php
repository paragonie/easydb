<?php
namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use PDO;
use PDOException;

class QTest
    extends
        RunTest
{


    protected function getResultForMethod(EasyDB $db, $statement, $offset, $params)
    {
        $args = $params;
        array_unshift($args, $statement);

        return call_user_func_array([$db, 'q'], $args);
    }
}
