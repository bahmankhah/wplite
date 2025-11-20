<?php

namespace WPLite;

use BadMethodCallException;

class Model
{
    protected $table;
    protected $primaryKey = 'id';
    protected $queryBuilder = [];
    protected $wpdb;
    protected $tableAlias = '';
    protected $postType = null;
    protected $attributes = [];


    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->newQuery();
    }

    public function getWpdb()
    {
        return $this->wpdb;
    }

    public function create($data, $dataTypes = null){
        $inserted = $this->wpdb->insert(
            $this->table,
            $data,
            $dataTypes,
        );
        return $inserted;
    }

    public function newQuery()
    {
        $this->queryBuilder = [
            'select' => '*',
            'joins' => [],
            'where' => [],
            'orderBy' => '',
            'groupBy' => '',
            'limit' => '',
            'relations' => [
                'hasMany' => [],
                'hasOne' => [],
                'belongsTo' => [],
            ],
            'hide' => [],
        ];

        if ($this->postType) {
            $this->where('post_type', '=', $this->postType);
        }

        return $this;
    }

    public function setTableAlias($alias)
    {
        $this->tableAlias = $alias;
        return $this;
    }

    public function setTable($table, $alias = null)
    {
        $this->table = $table;
        if ($alias) {
            $this->setTableAlias($alias);
        }
        return $this;
    }

    public function select($columns)
    {
        $this->queryBuilder['select'] = is_array($columns) ? implode(',', $columns) : $columns;
        return $this;
    }

    public function join($table, $first, $operator, $second, $type = 'INNER')
    {
        $this->queryBuilder['joins'][] = "{$type} JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    public function where($column, $operator, $value, $type = '%s')
    {  
        if($type == 'none'){
            $this->queryBuilder['where'][] = $this->wpdb->prepare("{$column} {$operator} {$value}");

        }else{
        $this->queryBuilder['where'][] = $this->wpdb->prepare("{$column} {$operator} {$type}", $value);

        }
        return $this;
    }

    public function orderBy($column, $direction = 'ASC')
    {
        $this->queryBuilder['orderBy'] = "ORDER BY {$column} {$direction}";
        return $this;
    }

    public function groupBy($columns)
    {
        $this->queryBuilder['groupBy'] = is_array($columns) ? implode(',', $columns) : $columns;
        return $this;
    }
    public function limit($limit)
    {
        $this->queryBuilder['limit'] = "LIMIT {$limit}";
        return $this;
    }

    public function sql(){
        $joins = !empty($this->queryBuilder['joins']) ? implode(' ', $this->queryBuilder['joins']) : '';
        $where = !empty($this->queryBuilder['where']) ? 'WHERE ' . implode(' AND ', $this->queryBuilder['where']) : '';
        $groupBy = !empty($this->queryBuilder['groupBy']) ? 'GROUP BY '. $this->queryBuilder['groupBy'] : '';
        
        $sql = "SELECT {$this->queryBuilder['select']} FROM {$this->table} {$this->tableAlias} {$joins} {$where} {$groupBy} {$this->queryBuilder['orderBy']} {$this->queryBuilder['limit']}";
        return $sql;
    }
    public function update(array $data, array $where, $format = null, $where_format = null ){
        return $this->wpdb->update(
            $this->table,
            $data,
            $where,
            $format,
            $where_format
        );
    }

    public function delete( array $where, $where_format = null ){
        return $this->wpdb->delete(
            $this->table,
            $where,
            $where_format,
        );
    }
    public function get()
    {
        
        $results = $this->wpdb->get_results($this->sql(), 'ARRAY_A');
        
        foreach ($results as &$result) {
            $this->attributes = $result;
            foreach ($this->queryBuilder['relations'] as $type => $relations) {
                foreach ($relations as $name => $args) {
                    $result[$name] = call_user_func_array([$this, "{$type}Method"], $args);
                }
            }
            foreach ($this->queryBuilder['hide'] as $name) {
                unset($result[$name]);
            }
        }
        $this->newQuery();
        
        return $results;
    }

    public function with($name, $method){
        $this->queryBuilder['relations']['with'][$name] = [$method];
        return $this;
    }
    private function withMethod($method){
        return call_user_func($method, $this->attributes);
    }

    public function first()
    {
        $this->limit(1);
        $result = $this->get();
        $this->attributes = !empty($result) ? $result[0] : [];
        return $this->attributes;
    }

    private function getCallingFunctionName()
    {
        $backtrace = debug_backtrace();
        return $backtrace[2]['function'];
    }

    public function hide($name){
        $this->queryBuilder['hide'][] = $name;
        return $this;
    }

    public function hasMany($relatedTable, $foreignKey, $localKey = null)
    {
        $name = $this->getCallingFunctionName();
        $this->queryBuilder['relations']['hasMany'] = array_merge(
            $this->queryBuilder['relations']['hasMany'] ?? [],
            [$name => [$relatedTable, $foreignKey, $localKey]]
        );
        return $this;
    }

    private function hasManyMethod($relatedTable, $foreignKey, $localKey = null)
    {
        $localKey = $localKey ?: $this->primaryKey;
        $query = new self();
        $query->setTable($relatedTable)->where($foreignKey, '=', $this->attributes[$localKey] ?? null, '%d');
        $result = $query->get();
        return $result;
    }

    public function hasOne($relatedTable, $foreignKey, $localKey = null)
    {
        $name = $this->getCallingFunctionName();
        $this->queryBuilder['relations']['hasOne'] = array_merge(
            $this->queryBuilder['relations']['hasOne'] ?? [],
            [$name => [$relatedTable, $foreignKey, $localKey]]
        );
        return $this;
    }

    private function hasOneMethod($relatedTable, $foreignKey, $localKey = null)
    {
        $localKey = $localKey ?: $this->primaryKey;
        $query = new self();
        $query->setTable($relatedTable)->where($foreignKey, '=', $this->attributes[$localKey] ?? null, '%d');
        $result = $query->first(); // Only fetch the first record
        return $result;
    }

    public function belongsTo($relatedTable, $localKey, $foreignKey = null)
    {
        $name = $this->getCallingFunctionName();
        $this->queryBuilder['relations']['belongsTo'] = array_merge(
            $this->queryBuilder['relations']['belongsTo'] ?? [],
            [$name => [$relatedTable, $localKey, $foreignKey]]
        );
        return $this;
    }

    private function belongsToMethod($relatedTable, $localKey, $foreignKey = null)
    {
        $foreignKey = $foreignKey ?: $this->primaryKey;
        $query = new self();
        $query->setTable($relatedTable)->where($foreignKey, '=', $this->attributes[$localKey] ?? null, '%d');
        $result = $query->first(); // Only fetch the first record
        return $result;
    }

    public function hasOneMeta($relatedTable, $metaKey,$localKey, $foreignKey = null, $valueField = 'meta_value', $keyField = 'meta_key')
    {
        $name = $this->getCallingFunctionName();
        $this->queryBuilder['relations']['hasOneMeta'] = array_merge(
            $this->queryBuilder['relations']['hasOneMeta'] ?? [],
            [$name => [$relatedTable, $metaKey,$localKey, $foreignKey]]
        );
        return $this;
    }

    private function hasOneMetaMethod($relatedTable, $metaKey,$localKey, $foreignKey = null, $valueField = 'meta_value', $keyField = 'meta_key')
    {
        $foreignKey = $foreignKey ?: $this->primaryKey;
        $query = new self();
        $query->setTable($relatedTable)->select($valueField)
        ->where($foreignKey, '=', $this->attributes[$localKey] ?? null, '%d')
        ->where($keyField, '=', $metaKey, '%s');
        $result = $query->first();
        return $result[$valueField];
    }


}
