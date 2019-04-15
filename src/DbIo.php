<?php

namespace sigalx\dbio;

class DbIoException extends \Exception
{
}

abstract class DbIoRowIterator implements \Iterator
{
    /** @var DbIoQueryResult */
    protected $_queryResult;
    /** @var array */
    protected $_currentRow;
    /** @var int */
    protected $_currentRowIndex;

    public function __construct(DbIoQueryResult $queryResult)
    {
        $this->_queryResult = $queryResult;
    }

    public function __destruct()
    {
    }

    public function current(): array
    {
        return $this->_currentRow;
    }

    public function key(): int
    {
        return $this->_currentRowIndex;
    }
}

abstract class DbIoQueryResult
{
    /** @var DbIoPreparedStatement */
    protected $_stmt;

    public function __construct(DbIoPreparedStatement $stmt)
    {
        $this->_stmt = $stmt;
    }

    public function __destruct()
    {
    }

    public function getStatement(): DbIoPreparedStatement
    {
        return $this->_stmt;
    }

    abstract public function createIterator(): DbIoRowIterator;

    abstract public function fetchRow($fullColumnNames = false): ?array;
}

abstract class DbIoPreparedStatement
{
    const TYPE_NULL = 0;
    const TYPE_BOOLEAN = 1;
    const TYPE_INTEGER = 2;
    const TYPE_BIGINT = 3;
    const TYPE_FLOAT = 4;
    const TYPE_DOUBLE = 5;
    const TYPE_STRING = 6;
    const TYPE_BINARY = 7;

    protected static $_phpTypeMap = [
        'NULL' => DbIoPreparedStatement::TYPE_NULL,
        'boolean' => DbIoPreparedStatement::TYPE_BOOLEAN,
        'integer' => DbIoPreparedStatement::TYPE_INTEGER,
        'float' => DbIoPreparedStatement::TYPE_FLOAT,
        'double' => DbIoPreparedStatement::TYPE_DOUBLE,
        'string' => DbIoPreparedStatement::TYPE_STRING,
    ];

    /** @var DbIo */
    protected $_db;
    /** @var string */
    protected $_sql;
    /** @var \stdClass[] */
    protected $_params = [];

    public function __construct(DbIo $db, string $sql)
    {
        $this->_db = $db;
        $this->_sql = trim($sql);
    }

    public function __destruct()
    {
    }

    /**
     * @param string $name
     * @param $value
     * @param int|null $type
     * @return DbIoPreparedStatement
     * @throws DbIoException
     */
    public function bindParam(string $name, $value, int $type = null): DbIoPreparedStatement
    {
        if (!isset($type)) {
            $phpType = gettype($value);
            if (!isset(static::$_phpTypeMap[$phpType])) {
                throw new DbIoException("Invalid value type {$phpType}");
            }
            $type = static::$_phpTypeMap[$phpType];
        }
        $param = new \stdClass();
        $param->value = $value;
        $param->type = $type;
        $this->_params[$name] = $param;
        return $this;
    }

    /**
     * @param array $params
     * @return DbIoPreparedStatement
     * @throws DbIoException
     */
    public function bindParams(array $params): DbIoPreparedStatement
    {
        foreach ($params as $name => $value) {
            $this->bindParam($name, $value);
        }
        return $this;
    }

    abstract public function reset(): DbIoPreparedStatement;

    abstract public function execute(): DbIoPreparedStatement;

    abstract public function isExecuted(): bool;

    abstract public function query(): DbIoQueryResult;

    public function queryIterator(): DbIoRowIterator
    {
        return $this->query()->createIterator();
    }

    public function queryRow(): ?array
    {
        return $this->query()->fetchRow();
    }

    public function queryAll(): array
    {
        $result = [];
        foreach ($this->queryIterator() as $queryRow) {
            $result[] = $queryRow;
        }
        return $result;
    }

    public function queryColumn(): array
    {
        $result = [];
        foreach ($this->queryIterator() as $queryRow) {
            $value = reset($queryRow);
            $result[] = $value;
        }
        return $result;
    }

    public function queryScalar()
    {
        $queryRow = $this->queryRow();
        if (!isset($queryRow)) {
            return false;
        }
        return reset($queryRow);
    }

    abstract public function getAffectedRows(): int;
}

abstract class DbIo
{
    /** @var DbIo */
    private static $_instance;

    /**
     * Used in global single-instance-style
     * @var array
     */
    public static $credentials = [];

    /**
     * For global single-instance-style using
     * @return DbIo
     */
    public static function instance(): DbIo
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new static(static::$credentials);
        }
        return self::$_instance;
    }

    /**
     * @param array $credentials
     */
    public function __construct(array $credentials = [])
    {
        // nothing to do
    }

    private function __clone()
    {
    }

    /**
     * @param string $sql
     * @return DbIoPreparedStatement
     * @throws DbIoException
     */
    abstract public function prepare(string $sql): DbIoPreparedStatement;

    /**
     * @param DbIoQuery $query
     * @return DbIoPreparedStatement
     * @throws DbIoException
     */
    public function prepareQuery(DbIoQuery $query): DbIoPreparedStatement
    {
        return $this->prepare($query->getSql())->bindParams($query->getParams());
    }

}
