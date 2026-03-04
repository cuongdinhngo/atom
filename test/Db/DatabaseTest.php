<?php

namespace Atom\Test\Db;

use Atom\Test\TestCase;
use Atom\Db\Database;
use Atom\Db\Exception\DatabaseException;

/**
 * Test Database query builder by using a mock DB driver.
 * We extend Database to bypass the constructor's env() calls
 * and inject a mock DB connection directly.
 */
class TestableDatabase extends Database
{
    public function __construct()
    {
        // Skip parent constructor (no real DB connection)
        $this->db = new MockDbDriver();
    }

    // Expose protected buildQuery for testing
    public function testBuildQuery(string $type): string
    {
        return $this->buildQuery($type);
    }
}

/**
 * Minimal mock of the MySQL driver interface
 */
class MockDbDriver
{
    public function escape($data)
    {
        return addslashes($data);
    }

    public function query($sql)
    {
        return true;
    }

    public function error()
    {
        return '';
    }

    public function resultToArray($result)
    {
        return [];
    }

    public function lastestInsertId()
    {
        return 1;
    }

    public function beginTransaction()
    {
        return true;
    }

    public function commit()
    {
        return true;
    }

    public function rollback()
    {
        return true;
    }
}

class DatabaseTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new TestableDatabase();
    }

    // --- table() ---

    public function testTableReturnsSelf()
    {
        $result = $this->db->table('users');
        $this->assertSame($this->db, $result);
    }

    public function testCheckTableReturnsTrueWhenSet()
    {
        $this->db->table('users');
        $this->assertTrue($this->db->checkTable());
    }

    public function testCheckTableReturnsFalseWhenNotSet()
    {
        $this->assertFalse($this->db->checkTable());
    }

    // --- select() ---

    public function testSelectReturnsSelf()
    {
        $result = $this->db->table('users')->select(['name', 'email']);
        $this->assertSame($this->db, $result);
    }

    // --- buildQuery SELECT ---

    public function testBuildSelectQuery()
    {
        $this->db->table('users');
        $sql = $this->db->testBuildQuery(Database::QUERY_SELECT);

        $this->assertStringContainsString('SELECT', $sql);
        $this->assertStringContainsString('FROM users', $sql);
    }

    public function testBuildSelectWithColumns()
    {
        $this->db->table('users')->select(['name', 'email']);
        $sql = $this->db->testBuildQuery(Database::QUERY_SELECT);

        $this->assertStringContainsString('`name`', $sql);
        $this->assertStringContainsString('`email`', $sql);
    }

    // --- where() ---

    public function testWhereSimpleCondition()
    {
        $this->db->table('users')->where(['id', '1']);
        $sql = $this->db->testBuildQuery(Database::QUERY_SELECT);

        $this->assertStringContainsString('WHERE', $sql);
        $this->assertStringContainsString('id', $sql);
    }

    public function testWhereWithOperator()
    {
        $this->db->table('users')->where(['age', '>', '18']);
        $sql = $this->db->testBuildQuery(Database::QUERY_SELECT);

        $this->assertStringContainsString('>', $sql);
    }

    public function testWhereMultipleConditions()
    {
        $this->db->table('users')->where([['id', '1'], ['status', 'active']]);
        $sql = $this->db->testBuildQuery(Database::QUERY_SELECT);

        $this->assertStringContainsString('AND', $sql);
    }

    public function testWhereThrowsOnEmptyArray()
    {
        $this->expectException(DatabaseException::class);
        $this->db->table('users')->where([]);
    }

    // --- orWhere() ---

    public function testOrWhere()
    {
        $this->db->table('users')->orWhere(['status', 'active']);
        $sql = $this->db->testBuildQuery(Database::QUERY_SELECT);

        $this->assertStringContainsString('OR', $sql);
    }

    // --- whereNull / whereNotNull ---

    public function testWhereNull()
    {
        $this->db->table('users')->whereNull('deleted_at');
        $sql = $this->db->testBuildQuery(Database::QUERY_SELECT);

        $this->assertStringContainsString('IS NULL', $sql);
        $this->assertStringContainsString('deleted_at', $sql);
    }

    public function testWhereNotNull()
    {
        $this->db->table('users')->whereNotNull('email');
        $sql = $this->db->testBuildQuery(Database::QUERY_SELECT);

        $this->assertStringContainsString('IS NOT NULL', $sql);
    }

    // --- whereBetween / whereNotBetween ---

    public function testWhereBetween()
    {
        $this->db->table('users')->whereBetween('age', ['18', '65']);
        $sql = $this->db->testBuildQuery(Database::QUERY_SELECT);

        $this->assertStringContainsString('BETWEEN', $sql);
    }

    public function testWhereNotBetween()
    {
        $this->db->table('users')->whereNotBetween('age', ['0', '17']);
        $sql = $this->db->testBuildQuery(Database::QUERY_SELECT);

        $this->assertStringContainsString('NOT BETWEEN', $sql);
    }

    // --- whereIn / whereNotIn ---

    public function testWhereIn()
    {
        $this->db->table('users')->whereIn('status', ['active', 'pending']);
        $sql = $this->db->testBuildQuery(Database::QUERY_SELECT);

        $this->assertStringContainsString('IN (', $sql);
    }

    public function testWhereNotIn()
    {
        $this->db->table('users')->whereNotIn('status', ['banned']);
        $sql = $this->db->testBuildQuery(Database::QUERY_SELECT);

        $this->assertStringContainsString('NOT IN (', $sql);
    }

    // --- limit / offset ---

    public function testLimitReturnsSelf()
    {
        $result = $this->db->table('users')->limit(10);
        $this->assertSame($this->db, $result);
    }

    public function testOffsetReturnsSelf()
    {
        $result = $this->db->table('users')->offset(5);
        $this->assertSame($this->db, $result);
    }

    public function testLimitInQuery()
    {
        $this->db->table('users')->limit(10)->offset(0);
        $sql = $this->db->testBuildQuery(Database::QUERY_SELECT);

        $this->assertStringContainsString('LIMIT', $sql);
    }

    public function testLimitThrowsOnNonInt()
    {
        $this->expectException(DatabaseException::class);
        $this->db->table('users')->limit('abc');
    }

    public function testOffsetThrowsOnNonInt()
    {
        $this->expectException(DatabaseException::class);
        $this->db->table('users')->offset('abc');
    }

    // --- groupBy ---

    public function testGroupBy()
    {
        $this->db->table('users')->groupBy('role');
        $sql = $this->db->testBuildQuery(Database::QUERY_SELECT);

        $this->assertStringContainsString('GROUP BY', $sql);
        $this->assertStringContainsString('role', $sql);
    }

    // --- orderBy ---

    public function testOrderBy()
    {
        $this->db->table('users')->orderBy('name', 'ASC');
        $sql = $this->db->testBuildQuery(Database::QUERY_SELECT);

        $this->assertStringContainsString('ORDER BY', $sql);
        $this->assertStringContainsString('ASC', $sql);
    }

    public function testOrderByThrowsOnInvalidSort()
    {
        $this->expectException(DatabaseException::class);
        $this->db->table('users')->orderBy('name', 'INVALID');
    }

    // --- having ---

    public function testHaving()
    {
        $this->db->table('users')->having('count', '>', '5');
        $sql = $this->db->testBuildQuery(Database::QUERY_SELECT);

        $this->assertStringContainsString('HAVING', $sql);
    }

    public function testHavingThrowsOnInvalidOperator()
    {
        $this->expectException(DatabaseException::class);
        $this->db->table('users')->having('count', 'INVALID', '5');
    }

    // --- Joins ---

    public function testInnerJoin()
    {
        $this->db->table('users')->innerJoin('orders', 'users.id', 'orders.user_id');
        $sql = $this->db->testBuildQuery(Database::QUERY_SELECT);

        $this->assertStringContainsString('INNER JOIN', $sql);
        $this->assertStringContainsString('orders', $sql);
    }

    public function testLeftJoin()
    {
        $this->db->table('users')->leftJoin('orders', 'users.id', 'orders.user_id');
        $sql = $this->db->testBuildQuery(Database::QUERY_SELECT);

        $this->assertStringContainsString('LEFT JOIN', $sql);
    }

    public function testRightJoin()
    {
        $this->db->table('users')->rightJoin('orders', 'users.id', 'orders.user_id');
        $sql = $this->db->testBuildQuery(Database::QUERY_SELECT);

        $this->assertStringContainsString('RIGHT JOIN', $sql);
    }

    public function testInnerJoinThrowsOnWrongArgCount()
    {
        $this->expectException(DatabaseException::class);
        $this->db->table('users')->innerJoin('orders', 'users.id');
    }

    // --- buildQuery DELETE ---

    public function testBuildDeleteQuery()
    {
        $this->db->table('users');
        $sql = $this->db->testBuildQuery(Database::QUERY_DELETE);

        $this->assertStringContainsString('DELETE FROM users', $sql);
    }

    // --- buildQuery TRUNCATE ---

    public function testBuildTruncateQuery()
    {
        $this->db->table('users');
        $sql = $this->db->testBuildQuery(Database::QUERY_TRUNCATE);

        $this->assertStringContainsString('TRUNCATE users', $sql);
    }

    // --- buildQuery UPDATE ---

    public function testBuildUpdateQuery()
    {
        $this->db->table('users');
        $this->db->parseUpdateValue(['name' => 'John']);
        $sql = $this->db->testBuildQuery(Database::QUERY_UPDATE);

        $this->assertStringContainsString('UPDATE users SET', $sql);
        $this->assertStringContainsString('name', $sql);
    }

    // --- buildQuery INSERT ---

    public function testBuildInsertQuery()
    {
        $this->db->table('users');
        $this->db->parseValues(['name' => 'John', 'email' => 'john@test.com']);
        $sql = $this->db->testBuildQuery(Database::QUERY_INSERT);

        $this->assertStringContainsString('INSERT INTO users', $sql);
        $this->assertStringContainsString('VALUES', $sql);
    }

    // --- Fillable ---

    public function testSetFillable()
    {
        $this->db->setFillable(['name', 'email']);
        $this->assertTrue($this->db->hasFillable());
    }

    public function testHasFillableReturnsFalse()
    {
        $this->assertFalse($this->db->hasFillable());
    }

    // --- Query Log ---

    public function testQueryLog()
    {
        $this->db->enableQueryLog();
        $this->db->table('users');
        $this->db->query('SELECT * FROM users');

        $log = $this->db->getQueryLog();
        $this->assertCount(1, $log);
        $this->assertStringContainsString('SELECT', $log[0]);
    }

    // --- parseConditions ---

    public function testParseConditionsWithTwoElements()
    {
        $result = $this->db->parseConditions(['id', '1']);
        $this->assertStringContainsString('id', $result);
        $this->assertStringContainsString('=', $result);
    }

    public function testParseConditionsWithThreeElements()
    {
        $result = $this->db->parseConditions(['age', '>', '18']);
        $this->assertStringContainsString('>', $result);
    }

    public function testParseConditionsThrowsOnSingleElement()
    {
        $this->expectException(DatabaseException::class);
        $this->db->parseConditions(['id']);
    }
}
