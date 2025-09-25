<?php
declare(strict_types=1);
namespace ParagonIE\EasyDB\Tests\Driver;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Exception\ConstructorFailed;
use ParagonIE\EasyDB\Factory;
use PDOException;
use PHPUnit\Framework\TestCase;

abstract class BaseTest extends TestCase
{
    /** @var ?EasyDB $db */
    protected ?EasyDB $db;

    /**
     * @return string
     */
    abstract protected function getDsn(): string;

    /**
     * @return string|null
     */
    protected function getUsername(): ?string
    {
        return null;
    }

    /**
     * @return string|null
     */
    protected function getPassword(): ?string
    {
        return null;
    }

    /**
     * @return array
     */
    protected function getOptions(): array
    {
        return [];
    }

    /**
     * @before
     */
    public function setUp(): void
    {
        try {
            $this->db = Factory::create(
                $this->getDsn(),
                $this->getUsername(),
                $this->getPassword(),
                $this->getOptions()
            );
            $this->db->run('DROP TABLE IF EXISTS users');
            if ($this->db->getDriver() === 'pgsql') {
                $this->db->run(
                    'CREATE TABLE users (
                    userid SERIAL PRIMARY KEY,
                    username TEXT,
                    email TEXT,
                    is_admin BOOLEAN DEFAULT FALSE
                )'
                );
            } elseif ($this->db->getDriver() === 'mysql') {
                $this->db->run(
                    'CREATE TABLE users (
                    userid INTEGER PRIMARY KEY AUTO_INCREMENT,
                    username TEXT,
                    email TEXT,
                    is_admin INTEGER DEFAULT 0
                )'
                );
            } else {
                $this->db->run(
                    'CREATE TABLE users (
                    userid INTEGER PRIMARY KEY AUTOINCREMENT,
                    username TEXT,
                    email TEXT,
                    is_admin INTEGER DEFAULT 0
                )'
                );
            }
        } catch (PDOException|ConstructorFailed $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }

    /**
     * @after
     */
    public function tearDown(): void
    {
        if (!($this->db instanceof EasyDB)) {
            return;
        }
        $this->db->run('DROP TABLE IF EXISTS users');
    }

    public function testInsertAndRow(): void
    {
        $this->db->insert('users', [
            'username' => 'testuser',
            'email' => 'test@example.com'
        ]);

        $row = $this->db->row('SELECT * FROM users WHERE username = ?', 'testuser');
        $this->assertEquals('testuser', $row['username']);
        $this->assertEquals('test@example.com', $row['email']);
    }

    public function testInsertGet(): void
    {
        $id = $this->db->insertGet('users', [
            'username' => 'testuser2',
            'email' => 'test2@example.com'
        ], 'userid');

        $this->assertIsNumeric($id);
        $this->assertGreaterThan(0, $id);

        $username = $this->db->cell('SELECT username FROM users WHERE userid = ?', $id);
        $this->assertEquals('testuser2', $username);
    }

    public function testUpdate()
    {
        $this->db->insert('users', [
            'username' => 'updateuser',
            'email' => 'update@example.com'
        ]);

        $this->db->update('users', ['email' => 'updated@example.com'], ['username' => 'updateuser']);

        $email = $this->db->cell('SELECT email FROM users WHERE username = ?', 'updateuser');
        $this->assertEquals('updated@example.com', $email);
    }

    public function testDelete()
    {
        $this->db->insert('users', [
            'username' => 'deleteuser',
            'email' => 'delete@example.com'
        ]);

        $this->assertTrue($this->db->exists('SELECT * FROM users WHERE username = ?', 'deleteuser'));
        $this->db->delete('users', ['username' => 'deleteuser']);
        $this->assertFalse($this->db->exists('SELECT * FROM users WHERE username = ?', 'deleteuser'));
    }

    public function testExists()
    {
        $this->assertFalse($this->db->exists('SELECT * FROM users WHERE username = ?', 'nouser'));
        $this->db->insert('users', [
            'username' => 'existsuser',
            'email' => 'exists@example.com'
        ]);
        $this->assertTrue($this->db->exists('SELECT * FROM users WHERE username = ?', 'existsuser'));
    }

    public function testSingle()
    {
        $this->db->insert('users', [
            'username' => 'singleuser',
            'email' => 'single@example.com'
        ]);
        $this->assertEquals('singleuser', $this->db->single('SELECT username FROM users WHERE username = ?', ['singleuser']));
    }

    public function testCell()
    {
        $this->db->insert('users', [
            'username' => 'celluser',
            'email' => 'cell@example.com'
        ]);
        $this->assertEquals('celluser', $this->db->cell('SELECT username FROM users WHERE username = ?', 'celluser'));
    }

    public function testCol()
    {
        $this->db->insert('users', ['username' => 'coluser1', 'email' => 'col1@example.com']);
        $this->db->insert('users', ['username' => 'coluser2', 'email' => 'col2@example.com']);

        $col = $this->db->col('SELECT username FROM users WHERE username LIKE ?', 0, 'coluser%');
        $this->assertCount(2, $col);
        $this->assertContains('coluser1', $col);
        $this->assertContains('coluser2', $col);
    }

    public function testCsv()
    {
        $this->db->insert('users', ['username' => 'csvuser1', 'email' => 'csv1@example.com']);
        $this->db->insert('users', ['username' => 'csvuser2', 'email' => 'csv2@example.com']);

        $csv = $this->db->csv('SELECT username, email FROM users WHERE username LIKE ?', 'csvuser%');
        $expected = [
            ['username', 'email'],
            ['csvuser1', 'csv1@example.com'],
            ['csvuser2', 'csv2@example.com']
        ];
        $this->assertEquals($expected, $csv);
    }

    public function testInvalidQuery()
    {
        $this->expectException(PDOException::class);
        $this->db->run('SELECT * FROM non_existent_table');
    }

    public function testSqlInjectionAttempt()
    {
        $this->db->insert('users', ['username' => 'admin', 'email' => 'admin@example.com']);
        $malicious = "' OR 1=1 --";
        $row = $this->db->row('SELECT * FROM users WHERE username = ?', $malicious);
        $this->assertEmpty($row);
    }
}
