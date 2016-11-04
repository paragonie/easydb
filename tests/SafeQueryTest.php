<?php

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
class SafeQueryTest extends RunTest
{
    protected function getResultForMethod(EasyDB $db, $statement, $offset, $params)
    {
        return $db->safeQuery($statement, $params);
    }
}