<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

#[CoversClass(EasyDB::class)]
class InsertManyFlatTransactionTestRuntimeException extends RuntimeException
{
}
