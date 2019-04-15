<?php

namespace sigalx\dbio\Query;

use sigalx\dbio\DbIoQuery;

class SelectQuery extends DbIoQuery
{
    /** @var string[] */
    protected $_columns = ['*'];
    /** @var string[] */
    protected $_from = [];
    /** @var string[] */
    protected $_group = [];
    /** @var string[] */
    protected $_order = [];
    /** @var int */
    protected $_limit;
    /** @var int|string */
    protected $_offset;

    public function __construct(array $columns = ['*'])
    {
        parent::__construct();
        $this->_columns = [];
        foreach ($columns as $column) {
            $this->_columns[] = $this->_prepareColumn($column);
        }
    }

    public function getSql(): string
    {
        $sql = 'SELECT ' . implode(',', $this->_columns);
        if ($fromClause = implode(', ', $this->_from)) {
            $sql .= " FROM {$fromClause}";
        }
        if ($whereClause = $this->getWhereClause()) {
            $sql .= " WHERE {$whereClause}";
        }
        if ($groupClause = $this->_getGroupClause()) {
            $sql .= " GROUP BY {$groupClause}";
        }
        if ($orderClause = $this->_getOrderClause()) {
            $sql .= " ORDER BY {$orderClause}";
        }
        if ($this->_limit !== null) {
            $sql .= " LIMIT {$this->_limit}";
        }
        if ($this->_offset !== null) {
            $sql .= " OFFSET {$this->_offset}";
        }
        return $sql;
    }

    public function fromTable(string $tableName, string $tableAlias = null): SelectQuery
    {
        if ($tableAlias) {
            $this->_from[] = "`{$tableName}` AS `{$tableAlias}`";
        } else {
            $this->_from[] = "`{$tableName}`";
        }
        return $this;
    }

    public function setOrConditions(bool $or = true): SelectQuery
    {
        $this->_orConditions = $or;
        return $this;
    }

    public function addCondition(string $condition): SelectQuery
    {
        $this->_addCondition($condition);
        return $this;
    }

    public function compare(string $column, $value, string $operator = '='): SelectQuery
    {
        $this->_compare($column, $value, $operator);
        return $this;
    }

    public function addInCondition(string $column, array $values): SelectQuery
    {
        $this->_addInCondition($column, $values);
        return $this;
    }

    public function addBetweenCondition(string $column, $from, $to, $notBetween = false): SelectQuery
    {
        $this->_addBetweenCondition($column, $from, $to, $notBetween);
        return $this;
    }

    public function mergeConditions(SelectQuery $query): SelectQuery
    {
        $this->_addCondition($query->getWhereClause());
        foreach ($query->getParams() as $param => $value) {
            $this->_params[$param] = $value;
        }
        return $this;
    }

    public function groupBy(string $column): SelectQuery
    {
        $this->_group[] = $this->_prepareColumn($column);
        return $this;
    }

    public function orderBy(string $column, $desc = false): SelectQuery
    {
        $this->_order[] = $this->_prepareColumn($column) . ($desc ? ' DESC' : ' ASC');
        return $this;
    }

    public function setLimit(int $limit = 10): SelectQuery
    {
        $this->_limit = $limit;
        return $this;
    }

    public function setOffset(int $offset = 0): SelectQuery
    {
        $this->_offset = $offset;
        return $this;
    }

    public function setOffsetParam(string $param): SelectQuery
    {
        $this->_offset = ":{$param}";
        return $this;
    }

    protected function _getGroupClause(): string
    {
        return implode(', ', $this->_group);
    }

    protected function _getOrderClause(): string
    {
        return implode(', ', $this->_order);
    }
}
