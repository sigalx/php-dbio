<?php

namespace sigalx\dbio\Query;

use sigalx\dbio\DbIoQuery;

class InsertQuery extends DbIoQuery
{
    /** @var string */
    protected $_tableClause;
    /** @var string[] */
    protected $_columns = null;
    /** @var array[] */
    protected $_rows = [];

    public function __construct(string $tableName, array $columns = null)
    {
        parent::__construct();
        $this->_tableClause = "`{$tableName}`";
        $this->_columns = $columns;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getSql(): string
    {
        if (!$this->_columns) {
            throw new \Exception('No data to insert');
        }
        $valuesClause = [];
        if ($this->_rows) {
            foreach ($this->_rows as $params) {
                $valuesClause[] = implode(',', $params);
            }
        } else {
            $valuesClause[] = implode(',', array_map(function ($column) {
                return ":{$column}";
            }, $this->_columns));
        }
        $valuesClause = implode(',', array_map(function ($i) {
            return "({$i})";
        },
            $valuesClause
        ));
        $columnsClause = implode(',', array_map(function ($column) {
            return $this->_prepareColumn($column);
        },
            $this->_columns
        ));
        return "INSERT INTO {$this->_tableClause} ({$columnsClause}) VALUES {$valuesClause}";
    }

    public function addRow(array $values): InsertQuery
    {
        if (!$this->_columns) {
            $this->_columns = array_keys($values);
        }
        $params = [];
        foreach ($values as $column => $value) {
            $params[] = $this->createNamedParam($value, $column);
        }
        $this->_rows[] = $params;
        return $this;
    }

}
