<?php

namespace sigalx\dbio\Query;

use sigalx\dbio\DbIoQuery;

class DeleteQuery extends DbIoQuery
{
    /** @var string */
    protected $_tableClause;
    /** @var int */
    protected $_limit;
    /** @var bool */
    public $safe = true;

    public function __construct(string $tableName)
    {
        parent::__construct();
        $this->_tableClause = "`{$tableName}`";
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getSql(): string
    {
        $sql = "DELETE FROM {$this->_tableClause}";
        if ($whereClause = $this->getWhereClause()) {
            $sql .= " WHERE {$whereClause}";
        } elseif ($this->safe) {
            throw new \Exception('Missing update condition in safe mode');
        }
        if ($this->_limit !== null) {
            $sql .= " LIMIT {$this->_limit}";
        }
        return $sql;
    }

    public function setSafe(bool $value): DeleteQuery
    {
        $this->safe = $value;
        return $this;
    }

    public function setOrConditions(bool $or = true): DeleteQuery
    {
        $this->_orConditions = $or;
        return $this;
    }

    public function addCondition(string $condition): DeleteQuery
    {
        $this->_addCondition($condition);
        return $this;
    }

    public function compare(string $column, $value, string $operator = '='): DeleteQuery
    {
        $this->_compare($column, $value, $operator);
        return $this;
    }

    public function addInCondition(string $column, array $values): DeleteQuery
    {
        $this->_addInCondition($column, $values);
        return $this;
    }

    public function addBetweenCondition(string $column, $from, $to, $notBetween = false): DeleteQuery
    {
        $this->_addBetweenCondition($column, $from, $to, $notBetween);
        return $this;
    }

    public function mergeConditions(SelectQuery $query): DeleteQuery
    {
        $this->_addCondition($query->getWhereClause());
        foreach ($query->getParams() as $param => $value) {
            $this->_params[$param] = $value;
        }
        return $this;
    }

    public function setLimit(int $limit = 10): DeleteQuery
    {
        $this->_limit = $limit;
        return $this;
    }
}
