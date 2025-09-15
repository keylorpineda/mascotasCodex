<?php
namespace App\Config;

class QueryBuilderFromArray
{
    private $data;
    private $selectColumns = ["*"];
    private $whereConditions;
    private $orderByColumn;
    private $orderByDirection;
    private $limit;
    private $offset;

    public function __construct(?array $array = null)
    {
    	if (isset($array)) {
        	$this->from($array);
    	}
    }

    public function from(array $array)
    {
        $this->data = $array;
        return $this;
    }

    public function select(string $columns = '*')
    {
        if ($columns !== '*') {
            $this->selectColumns = is_array($columns) ? $columns : explode(',', $columns);
        } else {
            $this->selectColumns = ['*'];
        }
        return $this;
    }

    public function where(array $conditions)
    {
        $this->whereConditions = $conditions;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc')
    {
        $this->orderByColumn = $column;
        $this->orderByDirection = strtolower($direction) === 'desc' ? 'desc' : 'asc';
        return $this;
    }

	public function limit(int $limit, int $offset = 0)
	{
	    $this->limit = $limit;
	    $this->offset = $offset;
	    return $this;
	}

	public function get()
	{
	    $filteredData = $this->data;

	    if (!empty($this->whereConditions)) {
	        $filteredData = array_filter($this->data, function ($item) {
	            foreach ($this->whereConditions as $key => $value) {
	                if (!isset($item->$key) || $item->$key != $value) {
	                    return false;
	                }
	            }
	            return true;
	        });
	    }

	    $resultData = [];

	    $filteredData = array_slice($filteredData, $this->offset, $this->limit);

	    foreach ($filteredData as $item) {
	        $selectedItem = (object) [];

	        if (in_array('*', $this->selectColumns)) {
	            $selectedItem = $item;
	        } else {
	            foreach ($this->selectColumns as $column) {
	                list($column, $alias) = array_pad(explode(" AS ", trim($column)), 2, null);

	                if (isset($item->$column)) {
	                    $colname = $alias ?? $column;
	                    $selectedItem->$colname = $item->$column;
	                }
	            }
	        }

	        $resultData[] = $selectedItem;
	    }

	    if ($this->orderByColumn) {
	        $resultData = $this->sortArrayByColumn($resultData, $this->orderByColumn, $this->orderByDirection);
	    }

	    return $resultData;
	}

    public function first()
    {
        $this->data = self::get();
        return array_shift($this->data);
    }

    public function insert(array $data)
    {
        $this->data[] = (object) $data;
        return $this->data;
    }

    public function update(array $data)
    {
        foreach ($this->data as $key => $item) {
            if ($this->isMatch($item, $this->whereConditions)) {
                foreach ($data as $column => $value) {
                    $this->data[$key]->$column = $value;
                }
            }
        }
        return $this->data;
    }

    public function delete()
    {
        foreach ($this->data as $key => $item) {
            if ($this->isMatch($item, $this->whereConditions)) {
                unset($this->data[$key]);
            }
        }
        $this->data = array_values($this->data);
        return $this->data;
    }

    private function sortArrayByColumn(string $array, string $column, $sortDirection = SORT_ASC)
    {
        $sortColumn = [];
        foreach ($array as $key => $row) {
            $sortColumn[$key] = $row->$column;
        }
        array_multisort($sortColumn, $sortDirection, $array);
        return $array;
    }

    private function isMatch(object $item, array $conditions)
    {
        foreach ($conditions as $key => $value) {
            if (!isset($item->$key) || $item->$key != $value) {
                return false;
            }
        }
        return true;
    }
}