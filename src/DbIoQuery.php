<?php

namespace sigalx\dbio;

abstract class DbIoQuery
{
    /** @var string */
    protected $_uniqid;
    /** @var array */
    protected $_params = [];
    /** @var bool */
    protected $_orConditions = false;
    /** @var string[] */
    protected $_conditions = [];

    public function __construct()
    {
        $this->_uniqid = substr(md5(uniqid()), -4);
    }

    abstract public function getSql(): string;

    public function getUniqid(): string
    {
        return $this->_uniqid;
    }

    public function getParams(): array
    {
        return $this->_params;
    }

    public function createNamedParam($value, string $hint = null): string
    {
        $paramCount = count($this->_params);
        $paramName = "__{$this->_uniqid}_{$paramCount}";
        if ($hint) {
            $paramName .= "_{$hint}";
        }
        $this->_params[$paramName] = $value;
        return ':' . $paramName;
    }

    protected function _prepareColumn(string $column, &$hint = null): string
    {
        return preg_replace_callback('/(([a-z][a-z0-9_]*)\.)?([a-z][a-z0-9_]*)/', function ($matches) use (&$hint) {
            $hint = $matches[3];
            if ($matches[2]) {
                return "`{$matches[2]}`.`{$matches[3]}`";
            }
            return "`{$matches[3]}`";
        },
            $column
        );
    }

    protected function _addCondition(string $condition): void
    {
        $this->_conditions[] = $condition;
    }

    protected function _compare(string $column, $value, string $operator = '='): void
    {
        $hint = null;
        $column = $this->_prepareColumn($column, $hint);

        $condition = null;
        if ($value === null) {
            if ($operator == '=') {
                $condition = "{$column} IS NULL";
            } elseif ($operator == '<>') {
                $condition = "{$column} IS NOT NULL";
            }
        } else {
            $paramName = $this->createNamedParam($value, $hint);
            $condition = "{$column} {$operator} {$paramName}";
        }
        $this->_addCondition($condition);
    }

    protected function _addInCondition(string $column, array $values, $notIn = false): void
    {
        $hint = null;
        $column = $this->_prepareColumn($column, $hint);

        if (!$values) {
            if (!$notIn && !$this->_orConditions) {
                $this->_addCondition('1=0');
            } elseif ($notIn && $this->_orConditions) {
                $this->_addCondition('1=1');
            }
        } else {
            $paramList = [];
            foreach ($values as $value) {
                $paramList[] = $this->createNamedParam($value, $hint);
            }
            $paramList = implode(',', $paramList);
            $operator = $notIn ? 'NOT IN' : 'IN';
            $this->_addCondition("{$column} {$operator} ({$paramList})");
        }
    }

    protected function _addBetweenCondition(string $column, $from, $to, $notBetween = false): void
    {
        $hint = null;
        $column = $this->_prepareColumn($column, $hint);
        $fromParam = $this->createNamedParam($from, $hint);
        $toParam = $this->createNamedParam($to, $hint);
        $operator = $notBetween ? 'NOT BETWEEN' : 'BETWEEN';
        $this->_addCondition("{$column} {$operator} {$fromParam} AND {$toParam}");
    }

    public function getWhereClause(): string
    {
        return implode(
            $this->_orConditions ? ' OR ' : ' AND ',
            array_map(function ($c) {
                return "({$c})";
            }, $this->_conditions)
        );
    }

    public function setWhereClause(string $condition): void
    {
        $this->_conditions = [$condition];
    }
}
