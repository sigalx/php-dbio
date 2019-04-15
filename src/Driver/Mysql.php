<?php

namespace sigalx\dbio\Driver;

use sigalx\dbio\DbIo;
use sigalx\dbio\DbIoException;
use sigalx\dbio\DbIoPreparedStatement;
use sigalx\dbio\DbIoQueryResult;
use sigalx\dbio\DbIoRowIterator;

include_once(__DIR__ . '/../DbIo.php');

class MysqlRowIterator extends DbIoRowIterator
{
    /** @var \mysqli_result */
    protected $_internal;

    /**
     * MysqlRowIterator constructor.
     * @param MysqlQueryResult $queryResult
     * @throws DbIoException
     * @throws \Exception
     */
    public function __construct(MysqlQueryResult $queryResult)
    {
        parent::__construct($queryResult);
        /** @var MysqlPreparedStatement $stmt */
        $stmt = $queryResult->getStatement();
        if (!$stmt->isExecuted()) {
            throw new \Exception("Trying to get the query result from non-executed statement");
        }
        $this->_internal = $stmt->getInternalObject()->get_result();
        if ($this->_internal === false) {
            throw new DbIoException("Unable to get the query result: ({$stmt->getInternalObject()->errno}) {$stmt->getInternalObject()->error}");
        }
    }

    public function __destruct()
    {
        if (!$this->_internal) {
            return;
        }
        $this->_internal->close();
    }

    public function getInternalObject(): \mysqli_result
    {
        return $this->_internal;
    }

    public function next(): void
    {
        $this->_currentRow = $this->_internal->fetch_assoc();
        $this->_currentRowIndex++;
    }

    public function rewind(): void
    {
        $this->_currentRow = $this->_internal->fetch_assoc();
        $this->_currentRowIndex = 0;
    }

    public function valid(): bool
    {
        return !empty($this->_currentRow);
    }
}

class MysqlQueryResult extends DbIoQueryResult
{
    /** @var array */
    protected $_fields;

    public function __destruct()
    {
        /** @var MysqlPreparedStatement $stmt */
        $stmt = $this->_stmt;
        $stmt->getInternalObject()->free_result();
    }

    /**
     * @return DbIoRowIterator
     * @throws DbIoException
     */
    public function createIterator(): DbIoRowIterator
    {
        return new MysqlRowIterator($this);
    }

    /**
     * @param bool $fullColumnNames
     * @return array|null
     * @throws \Exception
     */
    public function fetchRow($fullColumnNames = false): ?array
    {
        /** @var MysqlPreparedStatement $stmt */
        $stmt = $this->_stmt;
        /** @var \mysqli_stmt $internalStmt */
        $internalStmt = $stmt->getInternalObject();
        if ($this->_fields === null) {
            if (!$stmt->isExecuted()) {
                throw new \Exception("Trying to get the query result from non-executed statement");
            }

            $meta = $stmt->getInternalObject()->result_metadata();
            $this->_fields = [];
            while ($field = $meta->fetch_field()) {
                $this->_fields[] = $field;
            }
            $meta->close();
        }
        $queryRow = [];
        $stmtCallParams = [];
        foreach ($this->_fields as $field) {
            $fieldName = $field->name;
            if ($fullColumnNames) {
                $fieldName = "{$field->table}.{$field->name}";
            }
            $queryRow[$fieldName] = null;
            $stmtCallParams[] = &$queryRow[$fieldName]; // huck
        }
        if (!call_user_func_array([$internalStmt, 'bind_result'], $stmtCallParams)) {
            throw new DbIoException("Unable to call mysqli_stmt::bind_param() on the prepared statement: ({$internalStmt->errno}) {$internalStmt->error}");
        }
        $fetchResult = $internalStmt->fetch();
        if ($fetchResult === false) {
            throw new DbIoException("Unable to call mysqli_stmt::fetch() on the prepared statement: ({$internalStmt->errno}) {$internalStmt->error}");
        }
        if (!$fetchResult) {
            return null;
        }
        return $queryRow;
    }
}

class MysqlPreparedStatement extends DbIoPreparedStatement
{
    protected static $_mysqlTypeMap = [
        DbIoPreparedStatement::TYPE_NULL => 's',
        DbIoPreparedStatement::TYPE_BOOLEAN => 'i',
        DbIoPreparedStatement::TYPE_INTEGER => 'i',
        DbIoPreparedStatement::TYPE_BIGINT => 'i',
        DbIoPreparedStatement::TYPE_FLOAT => 'd',
        DbIoPreparedStatement::TYPE_DOUBLE => 'd',
        DbIoPreparedStatement::TYPE_STRING => 's',
//        DbPreparedStatement::TYPE_BINARY => 'b',
    ];

    /** @var \mysqli_stmt */
    protected $_stmt;

    /** @var int */
    protected $_paramNumber;

    /** @var bool */
    protected $_isExecuted = false;

    /**
     * MysqlPreparedStatement constructor.
     * @param Mysql $db
     * @param string $sql
     * @throws DbIoException
     * @throws \Exception
     */
    public function __construct(Mysql $db, string $sql)
    {
        parent::__construct($db, $sql);
        $this->_sql = preg_replace_callback('/\:[a-zA-Z_][a-zA-Z_0-9]*/', function ($matches) {
            $this->_params[substr($matches[0], 1)] = null;
            return '?';
        },
            $this->_sql, -1, $this->_paramNumber
        );
        $this->_stmt = $db->getInternalObject()->prepare($this->_sql);
        if ($this->_stmt === false) {
            throw new DbIoException("Unable to prepare the query \"{$this->_sql}\": ({$db->getInternalObject()->errno}) {$db->getInternalObject()->error}");
        }
        if ($this->_paramNumber != $this->_stmt->param_count) {
            throw new \Exception("Unable to replace named parameters in the query \"{$this->_sql}\"");
        }
    }

    public function __destruct()
    {
        $this->_stmt->free_result();
        $this->_stmt->close();
    }

    public function getInternalObject(): \mysqli_stmt
    {
        return $this->_stmt;
    }

    public function reset(): DbIoPreparedStatement
    {
        $this->_params = [];
        $this->_stmt->reset();
        $this->_stmt->free_result();
        $this->_isExecuted = false;
        return $this;
    }

    /**
     * @return DbIoPreparedStatement
     * @throws DbIoException
     * @throws \Exception
     */
    public function execute(): DbIoPreparedStatement
    {
        if (!$this->_isExecuted) {
            if (count(array_filter($this->_params)) != $this->_paramNumber) {
                throw new \Exception("Number of bound parameters is not matched with some in the prepared statement");
            }
            if ($this->_paramNumber) {
                $mysqlTypes = '';
                foreach ($this->_params as $param) {
                    $mysqlTypes .= static::$_mysqlTypeMap[$param->type];
                }
                $stmtCallParams = [$mysqlTypes];
                foreach ($this->_params as &$param) {
                    $stmtCallParams[] = &$param->value; // huck
                }
                unset($param);
                if (!call_user_func_array([$this->_stmt, 'bind_param'], $stmtCallParams)) {
                    throw new DbIoException("Unable to call mysqli_stmt::bind_param() on the prepared statement: ({$this->_stmt->errno}) {$this->_stmt->error}");
                }
            }
        }
        $this->_stmt->reset();
        if (!$this->_stmt->execute()) {
            throw new DbIoException("Unable to execute the query \"{$this->_sql}\": ({$this->_stmt->errno}) {$this->_stmt->error}");
        }
        $this->_isExecuted = true;
        return $this;
    }

    public function isExecuted(): bool
    {
        return $this->_isExecuted;
    }

    /**
     * @return DbIoQueryResult
     * @throws DbIoException
     */
    public function query(): DbIoQueryResult
    {
        $this->execute();
        return new MysqlQueryResult($this);
    }

    public function getAffectedRows(): int
    {
        return $this->_stmt->affected_rows;
    }
}

class Mysql extends DbIo
{
    /** @var \mysqli */
    protected $_mysqli;

    /**
     * Mysql constructor.
     * @param array $credentials
     * @throws DbIoException
     */
    public function __construct(array $credentials = [])
    {
        parent::__construct($credentials);
        $this->_mysqli = @new \mysqli(
            $credentials['host'] ?? 'localhost',
            $credentials['username'] ?? null,
            $credentials['password'] ?? null,
            $credentials['dbname'] ?? null,
            $credentials['port'] ?? 3306,
            $credentials['socket'] ?? null,
        );
        if ($this->_mysqli->connect_errno) {
            throw new DbIoException("Unable to connect to MySQL server: ({$this->_mysqli->connect_errno}) {$this->_mysqli->connect_error}");
        }
        $this->_mysqli->set_charset('utf8');
    }

    public function __destruct()
    {
        $this->_mysqli->close();
    }

    public function getInternalObject(): \mysqli
    {
        return $this->_mysqli;
    }

    /**
     * @param string $sql
     * @return DbIoPreparedStatement
     * @throws DbIoException
     */
    public function prepare(string $sql): DbIoPreparedStatement
    {
        return new MysqlPreparedStatement($this, $sql);
    }

}
