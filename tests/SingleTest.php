<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(EasyDB::class)]
class SingleTest extends CellTest
{
    protected function getResultForMethod(EasyDB $db, $statement, $offset, $params)
    {
        return $db->single($statement, $params);
    }
}
