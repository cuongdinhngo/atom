<?php

namespace Atom\Db;

use Atom\Db\Driver;
use Atom\Db\Exception\DatabaseException;
use Atom\Db\DatabaseInterface;

class Database implements DatabaseInterface
{
    protected $db;
    protected $table;
    protected $result;
    protected $query;
    protected $selectCols;
    protected $limit;
    protected $offset;
    protected $fillable;
    protected $insertKeys;
    protected $insertValues;
    protected $enableQueryLog = false;
    protected $queryLog = [];
    protected $whereOperators = ['=', '!=', '<>', '>', '>=', '<', '<=', 'like', 'LIKE'];
    protected $conditions = "";
    protected $groupBy;
    protected $having;
    protected $orderBy;
    protected $updateValues;
    protected $innerJoin;
    protected $leftJoin;
    protected $rightJoin;

    const QUERY_SELECT = "SELECT";
    const QUERY_INSERT = "INSERT";
    const QUERY_UPDATE = "UPDATE";
    const QUERY_DELETE = "DELETE";
    const QUERY_TRUNCATE = "TRUNCATE";
    const QUERY_INSERT_DUPLICATE = "INSERT_DUPLICATE";

    /**
     * Database construct
     */
    public function __construct()
    {
        $this->db = (new Driver())->createConnection();
    }

    /**
     * Prepare SQL query
     * @param  string $type SQL query
     * @return string
     */
    protected function buildQuery($type)
    {
        switch ($type) {
            case self::QUERY_TRUNCATE:
                $sql = "TRUNCATE {$this->table}";
                break;
            case self::QUERY_UPDATE:
                $where = !boolval($this->conditions) ? "" : "WHERE TRUE ". $this->conditions;
                $values = $this->updateValues;
                $sql = "UPDATE {$this->table} SET {$values} {$where}";
                break;
            case self::QUERY_DELETE:
                $where = !boolval($this->conditions) ? "" : "WHERE TRUE ". $this->conditions;
                $sql = "DELETE FROM {$this->table} {$where}";
                break;
            case self::QUERY_INSERT:
                $sql = "INSERT INTO {$this->table}({$this->insertKeys}) VALUES {$this->insertValues}";
                break;
            case self::QUERY_INSERT_DUPLICATE:
                $sql = "INSERT INTO {$this->table}({$this->insertKeys}) VALUES {$this->insertValues} ON DUPLICATE KEY UPDATE {$this->updateValues}";
                break;
            case self::QUERY_SELECT:
                $select = self::QUERY_SELECT;
                $limit = "";
                $where = !boolval($this->conditions) ? "" : "WHERE TRUE ". $this->conditions;
                $groupBy = $this->groupBy ? "GROUP BY ". $this->groupBy : "";
                $having = $this->having ? "HAVING ". $this->having : "";
                $orderBy = $this->orderBy ? "ORDER BY ". $this->orderBy : "";
                $innerJoin = $this->innerJoin ?? "";
                $leftJoin = $this->leftJoin ?? "";
                $rightJoin = $this->rightJoin ?? "";
                $join = $innerJoin . $leftJoin . $rightJoin;
                if (is_numeric($this->limit)) {
                    $offset = is_numeric($this->offset) ? $this->offset : 0;
                    $limit = " LIMIT {$offset}, {$this->limit}";
                }

                $columns = ($this->selectCols) ? $this->selectCols : "*";
                $sql = "{$select} {$columns} FROM {$this->table} {$join} {$where} {$groupBy} {$having} {$orderBy} {$limit}";
                break;
        }

        return $sql;
    }

    /**
     * TRUNCATE query
     * @return void
     */
    public function truncate()
    {
        if (false === $this->checkTable()) {
            return false;
        }
        $sql = $this->buildQuery(self::QUERY_TRUNCATE);
        $this->query($sql);
    }

    /**
     * DELETE query
     * @return void
     */
    public function delete()
    {
        if (false === $this->checkTable()) {
            return false;
        }
        $sql = $this->buildQuery(self::QUERY_DELETE);
        $this->query($sql);
    }

    /**
     * UPDATE query
     * @param  array  $data
     * @return void
     */
    public function update(array $data)
    {
        if (false === $this->checkTable()) {
            return false;
        }

        if (!is_array($data)) {
            throw new DatabaseException(DatabaseException::ERR_MSQ_BAD_REQUEST, DatabaseException::ERR_CODE_BAD_REQUEST);
        }
        $this->parseUpdateValue($data);
        $sql = $this->buildQuery(self::QUERY_UPDATE);
        $this->query($sql);
    }

    /**
     * Parse values for Update
     * @param  array  $data [description]
     * @return [type]       [description]
     */
    public function parseUpdateValue(array $data)
    {
        $values = [];
        foreach ($data as $key => $value) {
            list($key, $value) = $this->parseRawValue($key, $value);
            $values[] = "`{$key}`" .' = '.$value;
        }
        $this->updateValues = implode(' , ', $values);
    }

    /**
     * INSERT statement
     * @param  array  $request
     * @return void
     */
    public function insert(array $request)
    {
        if (false === $this->checkTable()) {
            return false;
        }

        if (!is_array($request) || is_array($request[0])) {
            throw new DatabaseException(DatabaseException::ERR_MSQ_BAD_REQUEST, DatabaseException::ERR_CODE_BAD_REQUEST);
        }
        $this->parseValues($request);
        $sql = $this->buildQuery(self::QUERY_INSERT);
        $this->query($sql);
        return $this->db->lastestInsertId();
    }

    /**
     * INSERT MANY
     * @param  array  $requests
     * @return void
     */
    public function insertMany(array $requests)
    {
        if (false === $this->checkTable()) {
            return false;
        }

        $tmp = [];
        if (empty($this->fillable) || !is_array($this->fillable)) {
            throw new DatabaseException(DatabaseException::ERR_MSQ_BAD_REQUEST, DatabaseException::ERR_CODE_BAD_REQUEST);
        }
        foreach ($requests as $request) {
            if (!is_array($request)) {
                throw new DatabaseException(DatabaseException::ERR_MSQ_BAD_REQUEST, DatabaseException::ERR_CODE_BAD_REQUEST);
            }
            if (empty($request)) {
                continue;
            }
            $this->parseValues($request);
            $tmp[] = $this->insertValues;
        }
        $this->insertKeys = implode(',', $this->fillable);
        $this->insertValues = implode(',', $tmp);
        $sql = $this->buildQuery(self::QUERY_INSERT);
        $this->query($sql);
    }

    /**
     * INSERT DUPLICATE KEY
     * @param  array  $request
     * @return void
     */
    public function insertDuplicate(array $request)
    {
        if (false === $this->checkTable()) {
            return false;
        }

        if (!is_array($request) || is_array($request[0])) {
            throw new DatabaseException(DatabaseException::ERR_MSQ_BAD_REQUEST, DatabaseException::ERR_CODE_BAD_REQUEST);
        }

        $this->parseValues($request, false);
        $this->parseUpdateValue($request);
        $sql = $this->buildQuery(self::QUERY_INSERT_DUPLICATE);
        $this->query($sql);
    }

    /**
     * INNER JOIN
     * @return $this
     */
    public function innerJoin()
    {
        if (func_num_args() != 3) {
            throw new DatabaseException(DatabaseException::ERR_MSG_INVALID_ARGUMENTS);
        }
        list($joinTable, $tableCond, $joinCond) = func_get_args();
        $this->innerJoin = " INNER JOIN {$this->escape($joinTable)} ON {$this->escape($tableCond)} = {$this->escape($joinCond)}";
        return $this;
    }

    /**
     * LEFT JOIN
     * @return $this
     */
    public function leftJoin()
    {
        if (func_num_args() != 3) {
            throw new DatabaseException(DatabaseException::ERR_MSG_INVALID_ARGUMENTS);
        }
        list($joinTable, $tableCond, $joinCond) = func_get_args();
        $this->leftJoin = " LEFT JOIN {$this->escape($joinTable)} ON {$this->escape($tableCond)} = {$this->escape($joinCond)}";
        return $this;
    }

    /**
     * RIGHT JOIN
     * @return $this
     */
    public function rightJoin()
    {
        if (func_num_args() != 3) {
            throw new DatabaseException(DatabaseException::ERR_MSG_INVALID_ARGUMENTS);
        }
        list($joinTable, $tableCond, $joinCond) = func_get_args();
        $this->rightJoin = " RIGHT JOIN {$this->escape($joinTable)} ON {$this->escape($tableCond)} = {$this->escape($joinCond)}";
        return $this;
    }

    /**
     * Set WHERE conditions
     * @param  mixed  $conditions
     * @return $this
     */
    public function where($conditions = [])
    {
        $where = [];

        if (!is_array($conditions) || empty($conditions)) {
            throw new DatabaseException(DatabaseException::ERR_MSG_INVALID_ARGUMENTS);
        }

        if (!is_array($conditions[0])) {
            $where[] = $this->parseConditions($conditions);
        } else {
            foreach ($conditions as $condition) {
                $where[] = $this->parseConditions($condition);
            }
        }

        $this->conditions .= " AND " . implode(' AND ', $where);
        return $this;
    }

    /**
     * Parse conditions
     * @param  array  $condition
     * @return mixed
     */
    public function parseConditions(array $condition)
    {
        switch (count($condition)) {
            case 1:
                throw new DatabaseException(DatabaseException::ERR_MSG_INVALID_ARGUMENTS);
                break;
            case 2:
                list($key, $value) = $condition;
                list($key, $escapeValue) = $this->parseRawValue($key, $value);
                return "{$this->escape($key)}" . ' = ' . $escapeValue;
                break;
            case 3:
                list($key, $operator, $value) = $condition;
                list($key, $escapeValue) = $this->parseRawValue($key, $value);
                $operator = strtoupper($operator);
                return !in_array($operator, $this->whereOperators) ?: "{$this->escape($key)}" . " {$this->escape($operator)} " . $escapeValue;
                break;
        }
    }

    /**
     * Parse raw value
     * @param  string $key
     * @param  mixed $value
     * @return string
     */
    public function parseRawValue($key, $value)
    {
        preg_match("/\#(.+)/", $key, $output);
        return $output && $output[1] ? [substr($key, 1), "{$this->escape($value)}"] : [$key, "'{$this->escape($value)}'"];
    }

    /**
     * Set OR where condition
     * @return $this;
     */
    public function orWhere()
    {
        if (func_num_args() != 2) {
            throw new DatabaseException(DatabaseException::ERR_MSG_INVALID_ARGUMENTS);
        }

        list($key, $value) = func_get_args();
        $this->conditions .= " OR {$this->escape($key)}" . ' = ' . "'{$this->escape($value)}'";
        return $this;

    }

    /**
     * Set BETWEEN condition
     * @return $this
     */
    public function whereBetween(...$params)
    {
        list($key, $values) = $this->parseWhereCondition($params);
        $this->conditions .= " AND " . "{$this->escape($key)} BETWEEN " . implode(' AND ', $values);

        return $this;
    }

    /**
     * Set NOT BETWEEN condition
     * @return $this
     */
    public function whereNotBetween(...$params)
    {
        list($key, $values) = $this->parseWhereCondition($params);
        $this->conditions .= " AND " . "{$this->escape($key)} NOT BETWEEN " . implode(' AND ', $values);

        return $this;
    }

    /**
     * Set NULL condition
     * @return $this
     */
    public function whereNull($key)
    {
        $this->conditions .= " AND " . "`{$this->escape($key)}` IS NULL ";

        return $this;
    }

    /**
     * Set NOT NULL condition
     * @return $this
     */
    public function whereNotNull($key)
    {
        $this->conditions .= " AND " . "`{$this->escape($key)}` IS NOT NULL ";

        return $this;
    }

    /**
     * Parse Where condition
     * @return array
     */
    public function parseWhereCondition($condition)
    {
        list($key, $values) = $condition;
        if (count($condition) != 2 || !is_array($values)) {
            throw new DatabaseException(DatabaseException::ERR_MSG_INVALID_ARGUMENTS);
        }
        $values = array_map(function ($value) {
            return "'{$this->escape($value)}'";
        }, $values);

        return [$key, $values];
    }

    /**
     * Set IN condition
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function whereIn(...$params)
    {
        list($key, $values) = $this->parseWhereCondition($params);
        $this->conditions .= " AND " . "{$this->escape($key)} IN (" . implode(', ', $values) . ")";

        return $this;
    }

    /**
     * Set NOT IN condition
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function whereNotIn(...$params)
    {
        list($key, $values) = $this->parseWhereCondition($params);
        $this->conditions .= " AND " . "{$this->escape($key)} NOT IN (" . implode(', ', $values) . ")";

        return $this;
    }

    /**
     * Check table
     * @return boolean
     */
    public function checkTable()
    {
        return (bool) $this->table;
    }

    /**
     * Set table
     * @param  string $table Table
     * @return void
     */
    public function table(string $table)
    {
        $this->table = $table;

        return $this;
    }

    /**
     * SQL ESCAPE
     * @param  mixed $data
     * @return string
     */
    public function escape($data)
    {
        if (is_array($data)) {
            return array_map(function ($item) {
                return $this->db->escape($item);
            }, $data);
        }
        return $this->db->escape($data);
    }

    /**
     * SQL error
     * @return string
     */
    public function error()
    {
        return $this->db->error();
    }

    /**
     * EXECUTE SQL query
     * @param  string $sql
     * @return mixed
     */
    public function query(string $sql)
    {
        if ($this->enableQueryLog) {
            $this->queryLog[] = $sql;
        }
        if (!$result = $this->db->query(trim($sql))) {
            throw new DatabaseException($this->error());
        }

        return $result;
    }

    /**
     * Set SELECT columns
     * @param  array  $cols
     * @return $this
     */
    public function select($cols = [])
    {
        $columns = $this->parseRawKey($cols);
        $this->selectCols = implode(',', $columns);

        return $this;
    }

    /**
     * Parse raw key
     * @param  array  $keys
     * @return array
     */
    public function parseRawKey(array $keys)
    {
        return array_map(function ($key) {
            preg_match("/\#(.+)/", $key, $output);
            return $output && $output[1] ? "{$this->escape($output[1])}" : "`{$this->escape($key)}`";
        }, $keys);
    }

    /**
     * Set OFFSET
     * @param  mixed $offset
     * @return $this
     */
    public function offset($offset)
    {
        if (!is_int($offset)) {
            throw new DatabaseException(DatabaseException::ERR_MSG_INVALID_ARGUMENTS);
        }
        $this->offset = $offset;
        return $this;
    }

    /**
     * Set LIMIT
     * @param  mixed $limit
     * @return $this
     */
    public function limit($limit)
    {
        if (!is_int($limit)) {
            throw new DatabaseException(DatabaseException::ERR_MSG_INVALID_ARGUMENTS);
        }
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set GROUP BY
     * @param  string $key
     * @return mixed
     */
    public function groupBy($key)
    {
        if (is_string($key)) {
            $this->groupBy = $key;
            return $this;
        }
        return false;
    }

    /**
     * Set HAVING
     * @return $this
     */
    public function having()
    {
        list($key, $operator, $value) = func_get_args();
        if (func_num_args() != 3 || !in_array($operator, ['>', '=', '<'])) {
            throw new DatabaseException(DatabaseException::ERR_MSG_INVALID_ARGUMENTS);
        }

        preg_match("/\#(.+)/", $key, $output);
        $escapeKey = $output && $output[1] ? "{$this->escape($output[1])}" : "`{$this->escape($key)}`";
        $this->having = $escapeKey . " {$this->escape($operator)} " . "{$this->escape($value)}";

        return $this;
    }

    /**
     * Set ORDER BY
     * @return $this
     */
    public function orderBy()
    {
        list($key, $sort) = func_get_args();
        if (func_num_args() != 2 || !in_array(strtoupper($sort), ['ASC', 'DESC'])) {
            throw new DatabaseException(DatabaseException::ERR_MSG_INVALID_ARGUMENTS);
        }

        $this->orderBy = " {$this->escape($key)} " . "{$this->escape(strtoupper($sort))}";

        return $this;
    }

    /**
     * Return array responses
     * @param  int|null $limit
     * @return array
     */
    public function get($limit = null)
    {
        if (false === $this->checkTable()) {
            return false;
        }

        $this->limit = $limit ?? $this->limit;
        $sql = $this->buildQuery(self::QUERY_SELECT);
        $result = $this->query($sql);
        return $this->resultToArray($result);
    }

    /**
     * Get FIRST row
     * @return array
     */
    public function first()
    {
        return $this->get(1);
    }

    /**
     * Convert to array
     */
    public function resultToArray($result)
    {
        return $this->db->resultToArray($result);
    }

    /**
     * Parse request to keys & values
     * @param  array  $request
     * @param  boolean $checkFillable
     * @return void
     */
    public function parseValues(array $request, $checkFillable = true)
    {
        $parse = $request;
        if ($checkFillable && $this->hasFillable()) {
            $tmp = array_fill_keys($this->fillable, null);
            $parse = array_intersect_key($request, $tmp);
            //If total keys of request and fillable is different, keys are replace with null values
            $parse = array_replace($tmp, $parse);
        }

        $values = array_map(function ($value) {
            return !is_null($value) ? "{$this->escape($value)}" : $value;
        }, array_values($parse));

        $this->insertKeys = implode(',', array_keys($parse));
        $tmpValues = "('" . implode("','", $values) . "')";
        $this->insertValues = str_replace("''", 'null', $tmpValues);
    }

    /**
     * Enable query log
     * @return void
     */
    public function enableQueryLog()
    {
        return $this->enableQueryLog = true;
    }

    /**
     * Get query log
     * @return array
     */
    public function getQueryLog()
    {
        return $this->queryLog;
    }

    /**
     * Check $fillable
     * @return boolean
     */
    public function hasFillable()
    {
        return boolval($this->fillable);
    }

    /**
     * Chunk data rows
     * @param  int $count
     * @param  callable $callback
     * @return bool
     */
    public function chunk($count, $callback)
    {
        $page = 1;

        do {
            $results = $this->limit($count)->offset(($page - 1) * $count)->get();
            $countResults = count($results);

            if ($countResults == 0) {
                break;
            }

            if ($callback($results, $page) === false) {
                return false;
            }

            unset($results);
            $page++;
        } while ($countResults == $count);

        return true;
    }

    /**
     * Begin transaction
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->db->beginTransaction();
    }

    /**
     * Commit transaction
     * @return bool
     */
    public function commit()
    {
        return $this->db->commit();
    }

    /**
     * Roll back current transaction
     * @return bool
     */
    public function rollBack()
    {
        return $this->db->rollback();
    }

    /**
     * Destruct
     */
    public function __destruct()
    {
        unset($this->db);
    }

}
