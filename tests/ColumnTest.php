<?php

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
class ColumnTest extends ColTest
{
    protected function getResultForMethod(EasyDB $db, $statement, $offset, $params)
    {
        return $db->column($statement, $params, $offset);
    }
}