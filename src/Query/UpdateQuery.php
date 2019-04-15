<?php

namespace sigalx\dbio\Query;

use sigalx\dbio\DbIoQuery;

class UpdateQuery extends DbIoQuery
{
    /** @var string */
    protected $_tableClause;
    /** @var string[] */
    protected $_columns = null;
    /** @var string[] */
    protected $_data = null;
    /** @var bool */
    public $safe = true;

    public function __construct(string $tableName, array $columns = null, string $tableAlias = null)
    {
        parent::__construct();
        $this->_tableClause = "`{$tableName}`";
        if ($tableAlias) {
            $this->_tableClause = "`{$tableName}` AS `{$tableAlias}`";
        }
        $this->_columns = $columns;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getSql(): string
    {
        if (!$this->_columns) {
            throw new \Exception('No data to update');
        }
        $setClause = null;
        if ($this->_data) {
            foreach ($this->_data as $column => $param) {
                $setClause[] = "{$column}={$param}";
            }
        } else {
            foreach ($this->_columns as $column) {
                $hint = null;
                $column = $this->_prepareColumn($column, $hint);
                $setClause[] = "{$column}=:{$hint}";
            }
        }
        $setClause = implode(',', $setClause);
        $sql = "UPDATE {$this->_tableClause} SET {$setClause}";
        if ($whereClause = $this->getWhereClause()) {
            $sql .= " WHERE {$whereClause}";
        } elseif ($this->safe) {
            throw new \Exception('Missing update condition in safe mode');
        }
        return $sql;
    }

    public function setData(array $values): UpdateQuery
    {
        if (!$this->_columns) {
            $this->_columns = array_keys($values);
        }
        $this->_data = [];
        foreach ($values as $column => $value) {
            $this->_data[$column] = $this->createNamedParam($value, $column);
        }
        return $this;
    }

    public function setSafe(bool $value): UpdateQuery
    {
        $this->safe = $value;
        return $this;
    }

    public function setOrConditions(bool $or = true): UpdateQuery
    {
        $this->_orConditions = $or;
        return $this;
    }

    public function addCondition(string $condition): UpdateQuery
    {
        $this->_addCondition($condition);
        return $this;
    }

    public function compare(string $column, $value, string $operator = '='): UpdateQuery
    {
        $this->_compare($column, $value, $operator);
        return $this;
    }

    public function addInCondition(string $column, array $values): UpdateQuery
    {
        $this->_addInCondition($column, $values);
        return $this;
    }

    public function addBetweenCondition(string $column, $from, $to, $notBetween = false): UpdateQuery
    {
        $this->_addBetweenCondition($column, $from, $to, $notBetween);
        return $this;
    }

    public function mergeConditions(SelectQuery $query): UpdateQuery
    {
        $this->_addCondition($query->getWhereClause());
        foreach ($query->getParams() as $param => $value) {
            $this->_params[$param] = $value;
        }
        return $this;
    }
}
