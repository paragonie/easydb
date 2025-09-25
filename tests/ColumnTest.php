<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(EasyDB::class)]
class ColumnTest extends ColTest
{
    protected function getResultForMethod(EasyDB $db, $statement, $offset, $params)
    {
        return $db->column($statement, $params, $offset);
    }
}
