<?php

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
class SingleTest extends CellTest
{
    protected function getResultForMethod(EasyDB $db, $statement, $offset, $params)
    {
        return $db->single($statement, $params);
    }
}