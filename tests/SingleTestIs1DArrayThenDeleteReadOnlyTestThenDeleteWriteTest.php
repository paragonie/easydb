<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(EasyDB::class)]
#[CoversClass(Factory::class)]
class SingleTestIs1DArrayThenDeleteReadOnlyTestThenDeleteWriteTest extends EasyDBWriteTestCase
{

    /**
     * @dataProvider goodFactoryCreateArgument2EasyDBInsertManyProvider
     * @param callable $cb
     * @param array $insertMany
     */
    #[DataProvider("goodFactoryCreateArgument2EasyDBInsertManyProvider")]
    public function testDelete(callable $cb, array $insertMany)
    {
        $db = $this->easyDBExpectedFromCallable($cb);
        $db->insertMany('irrelevant_but_valid_tablename', $insertMany);
        $insertManyTotal = count($insertMany);
        $this->assertEquals(
            $db->single('SELECT COUNT(*) FROM irrelevant_but_valid_tablename'),
            $insertManyTotal
        );
        foreach ($insertMany as $insertVal) {
            $this->assertEquals(
                $db->single(
                    'SELECT COUNT(*) FROM irrelevant_but_valid_tablename WHERE foo = ?',
                    array_values($insertVal)
                ),
                1
            );
        }
        for ($i=0; $i<$insertManyTotal; ++$i) {
            $db->delete('irrelevant_but_valid_tablename', $insertMany[$i]);
            $this->assertEquals(
                $db->single('SELECT COUNT(*) FROM irrelevant_but_valid_tablename'),
                ($insertManyTotal - ($i + 1))
            );
        }
    }
}
