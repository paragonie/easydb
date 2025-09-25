<?php
declare(strict_types=1);
namespace ParagonIE\EasyDB\Tests\Driver;

class MysqlTest extends BaseTest
{
    /**
     * @return string
     */
    protected function getDsn(): string
    {
        $host = \getenv('MYSQL_HOST') ?: '127.0.0.1';
        $db = \getenv('MYSQL_DB') ?: 'easydb';
        return "mysql:host={$host};dbname={$db}";
    }

    /**
     * @return string
     */
    protected function getUsername(): string
    {
        return \getenv('MYSQL_USER') ?: 'root';
    }

    /**
     * @return string
     */
    protected function getPassword(): string
    {
        return \getenv('MYSQL_PASS') ?: '';
    }

    public function testIssue150(): void
    {
        try {
            $this->db->exec('CREATE TABLE bit1_test (id INT PRIMARY KEY, val BIT(1))');
        } catch (\Exception $ex) {
            $this->markTestSkipped('Cannot perform issue 150 test');
        }
        $this->db->insert('bit1_test', ['id' => 1, 'val' => true]);
        $this->db->insert('bit1_test', ['id' => 2, 'val' => false]);
        $this->db->insert('bit1_test', ['id' => 3, 'val' => true]);

        $this->assertSame(
            2,
            $this->db->single('SELECT COUNT(*) FROM bit1_test WHERE val = ?', [true])
        );

        $this->assertSame(
            1,
            $this->db->single('SELECT COUNT(*) FROM bit1_test WHERE val = ?', [false])
        );
    }
}
