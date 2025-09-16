<?php

namespace App\Config;

use Latitude\QueryBuilder\Query\MySql\SelectQuery;
use Latitude\QueryBuilder\Query\MySql\InsertQuery;
use Latitude\QueryBuilder\Query\MySql\UpdateQuery;
use Latitude\QueryBuilder\Query\MySql\DeleteQuery;
use Latitude\QueryBuilder\Query\SqlServer\SelectQuery as SqlServerSelectQuery;
use Latitude\QueryBuilder\Engine\SqlServerEngine;
use Latitude\QueryBuilder\Builder\LikeBuilder;
use Latitude\QueryBuilder\Engine\MySqlEngine;
use Latitude\QueryBuilder\QueryFactory;

use function Latitude\QueryBuilder\on;
use function Latitude\QueryBuilder\alias;
use function Latitude\QueryBuilder\param;
use function Latitude\QueryBuilder\field;
use function Latitude\QueryBuilder\search;
use function Latitude\QueryBuilder\identifyAll;
use stdClass;

class Model
{
    private $conn;
    private bool $in_transaction = false;

    // Nueva propiedad para composición en lugar de herencia
    private QueryFactory $queryFactory;

    private $query;
    private array $str_query = [];

    protected string $returnTypeDefault = "array";
    protected string $returnType        = "array";
    protected array  $data              = [];
    protected array  $allowedFields     = [];

    protected string $view       = "";
    protected string $table      = "";
    protected string $primaryKey = "";

    public $insertID     = null;
    public ?int $deletedRows  = null;
    public ?int $affectedRows = null;

    private bool $TableIsSet = false;

    public function __construct(?string $nombre_db = "default")
    {
        helper("database_helper");
        $ENGINE = [
            "mysql"  => (new MySqlEngine()),
            "sqlsrv" => (new SqlServerEngine()),
        ];
        $this->conn = data_base($nombre_db);
        $driver = get_driver($nombre_db);

        // Inicializar QueryFactory como propiedad en lugar de herencia
        $this->queryFactory = new QueryFactory($ENGINE[$driver]);
    }

    public function setQuery($query): self
    {
        $this->query = $query;
        return $this;
    }

    public function table(string $table, bool $resetType = false)
    {
        $this->table = $table;
        if ($resetType) {
            $this->returnType = $this->returnTypeDefault;
        }
        $this->TableIsSet = true;
        $this->select("*");
        return $this;
    }
    public function from(string $table, bool $resetType = false)
    {
        return $this->table($table, $resetType);
    }
    public function view()
    {
        $this->table = $this->view;
        $this->returnType = $this->returnTypeDefault;
        $this->table($this->table);
        return $this;
    }

    public function toArray()
    {
        $this->returnType = $this->returnTypeDefault;
        return $this;
    }

    public function toObject()
    {
        $this->returnType = "stdClass";
        return $this;
    }

    /**
     * Joins Methods
     */

    public function inner_join(string $table, string $left, string $right)
    {
        $this->query->innerJoin($table, on($left, $right));
        $this->returnType = $this->returnTypeDefault;
        return $this;
    }

    public function left_join(string $table, string $left, string $right)
    {
        $this->query->leftJoin($table, on($left, $right));
        $this->returnType = $this->returnTypeDefault;
        return $this;
    }

    public function right_join(string $table, string $left, string $right)
    {
        $this->query->rightJoin($table, on($left, $right));
        $this->returnType = $this->returnTypeDefault;
        return $this;
    }

    public function full_join(string $table, string $left, string $right)
    {
        $this->query->fullJoin($table, on($left, $right));
        $this->returnType = $this->returnTypeDefault;
        return $this;
    }

    /**
     * .\Joins Methods
     */

    /**
     * Find Methods
     */

    public function select(...$columns)
    {
        if (!$this->TableIsSet) {
            $this->table($this->table);
        }
        $columns = array_map(function ($item) {
            if (str_contains($item, " AS ")) {
                list($column, $alias) = explode(" AS ", $item);
                $item = alias($column, $alias);
            }
            return $item;
        }, $columns);

        // Usar queryFactory en lugar de herencia
        $this->setQuery($this->queryFactory->select(...$columns));
        $this->query->from($this->table);
        return $this;
    }

    public function where($data, ?string $val = null)
    {
        if (!is_array($data)) {
            $col = $data;
            $data = [$data => $val];
        }
        if (!$this->TableIsSet) {
            $this->select("*");
        }
        $where = "where";
        foreach ($data as $col => $val) {
            if (is_null($val)) {
                $this->query->{$where}(field($col)->isNull());
            } else {
                $this->query->{$where}(field($col)->eq($val));
            }
            $where = "andWhere";
        }
        return $this;
    }

    public function andWhere($data, ?string $val = null)
    {
        if (!is_array($data)) {
            $col = $data;
            $data = [$data => $val];
        }
        if (!$this->TableIsSet) {
            $this->select("*");
        }
        foreach ($data as $col => $val) {
            if (is_null($val)) {
                $this->query->andWhere(field($col)->isNull());
            } else {
                $this->query->andWhere(field($col)->eq($val));
            }
        }
        return $this;
    }

    public function orWhere($data, ?string $val = null)
    {
        if (!is_array($data)) {
            $col = $data;
            $data = [$data => $val];
        }
        if (!$this->TableIsSet) {
            $this->select("*");
        }
        foreach ($data as $col => $val) {
            if (is_null($val)) {
                $this->query->orWhere(field($col)->isNull());
            } else {
                $this->query->orWhere(field($col)->eq($val));
            }
        }
        return $this;
    }

    public function whereNot($data, ?string $val = null)
    {
        if (!is_array($data)) {
            $col = $data;
            $data = [$data => $val];
        }
        if (!$this->TableIsSet) {
            $this->select("*");
        }
        $where = "where";
        foreach ($data as $col => $val) {
            if (is_null($val)) {
                $this->query->{$where}(field($col)->isNotNull());
            } else {
                $this->query->{$where}(field($col)->notEq($val));
            }
            $where = "andWhere";
        }
        return $this;
    }

    public function andWhereNot($data, ?string $val = null)
    {
        if (!is_array($data)) {
            $col = $data;
            $data = [$data => $val];
        }
        if (!$this->TableIsSet) {
            $this->select("*");
        }
        foreach ($data as $col => $val) {
            if (is_null($val)) {
                $this->query->andWhere(field($col)->isNotNull());
            } else {
                $this->query->andWhere(field($col)->notEq($val));
            }
        }
        return $this;
    }

    public function orWhereNot($data, ?string $val = null)
    {
        if (!is_array($data)) {
            $col = $data;
            $data = [$data => $val];
        }
        if (!$this->TableIsSet) {
            $this->select("*");
        }
        foreach ($data as $col => $val) {
            if (is_null($val)) {
                $this->query->orWhere(field($col)->isNotNull());
            } else {
                $this->query->orWhere(field($col)->notEq($val));
            }
        }
        return $this;
    }

    public function whereIn($data, ?array $val = null)
    {
        if (!is_array($data)) {
            $col = $data;
            $data = [$data => $val];
        }
        if (!$this->TableIsSet) {
            $this->select("*");
        }
        $where = "where";
        foreach ($data as $col => $val) {
            $this->query->{$where}(field($col)->in(...$val));
            $where = "andWhere";
        }
        return $this;
    }

    public function andWhereIn($data, ?array $val = null)
    {
        if (!is_array($data)) {
            $col = $data;
            $data = [$data => $val];
        }
        if (!$this->TableIsSet) {
            $this->select("*");
        }
        foreach ($data as $col => $val) {
            $this->query->andWhere(field($col)->in(...$val));
        }
        return $this;
    }

    public function orWhereIn($data, ?array $val = null)
    {
        if (!is_array($data)) {
            $col = $data;
            $data = [$data => $val];
        }
        if (!$this->TableIsSet) {
            $this->select("*");
        }
        foreach ($data as $col => $val) {
            $this->query->orWhere(field($col)->in(...$val));
        }
        return $this;
    }

    public function like($data, ?string $val = null)
    {
        if (!is_array($data)) {
            $col = $data;
            $data = [$data => $val];
        }
        if (!$this->TableIsSet) {
            $this->select("*");
        }
        $where = "where";
        foreach ($data as $col => $val) {
            $this->query->{$where}(search($col)->contains($val));
            $where = "andWhere";
        }
        return $this;
    }

    public function andLike($data, ?string $val = null)
    {
        if (!is_array($data)) {
            $col = $data;
            $data = [$data => $val];
        }
        if (!$this->TableIsSet) {
            $this->select("*");
        }
        foreach ($data as $col => $val) {
            $this->query->andWhere(search($col)->contains($val));
        }
        return $this;
    }

    public function orLike($data, ?string $val = null)
    {
        if (!is_array($data)) {
            $col = $data;
            $data = [$data => $val];
        }
        if (!$this->TableIsSet) {
            $this->select("*");
        }
        foreach ($data as $col => $val) {
            $this->query->orWhere(search($col)->contains($val));
        }
        return $this;
    }

    public function between(string $column, $start, $end)
    {
        if (!$this->TableIsSet) {
            $this->select("*");
        }
        $this->query->where(field($column)->between($start, $end));
        return $this;
    }

    public function orBetween(string $column, $start, $end)
    {
        if (!$this->TableIsSet) {
            $this->select("*");
        }
        $this->query->orWhere(field($column)->between($start, $end));
        return $this;
    }

    public function andBetween(string $column, $start, $end)
    {
        if (!$this->TableIsSet) {
            $this->select("*");
        }
        $this->query->andWhere(field($column)->between($start, $end));
        return $this;
    }

    public function orderBy(string $col, string $direction = "asc")
    {
        $this->query->orderBy($col, strtolower($direction));
        return $this;
    }

    public function groupBy(string $col)
    {
        $this->query->groupBy($col);
        return $this;
    }

    public function limit(int $limit)
    {
        $this->query->limit($limit);
        return $this;
    }

    public function offset(int $offset)
    {
        $this->query->offset($offset);
        return $this;
    }

    public function getFirstRow()
    {
        $data = self::getAllRows();
        return array_shift($data);
    }

    public function getAllRows()
    {
        if (!$this->TableIsSet) {
            $this->select("*");
        }
        if (!isset($this->query)) {
            throw new \Exception("No existe un query sql para ejecutar", 1);
        }
        $stmt = self::prepareSql();
        $this->data = $stmt->fetchAll(CONSTANTES_PDO["AS_ARRAY"]);
        return self::toReturnType();
    }

    public function toReturnType()
    {
        $data = $this->data;
        if ($this->returnType === $this->returnTypeDefault) {
            return $data;
        }
        if (!class_exists($this->returnType)) {
            throw new \Exception("La clase entidad '{$this->returnType}' no existe", 1);
        }
        $return = [];
        foreach ($data as $value) {
            $class = new $this->returnType;
            foreach ($value as $key => $att) {
                if ($key != $this->primaryKey && !in_array($key, $this->allowedFields)) {
                    throw new \Exception("La propiedad '{$key}' no se encuentra definida en el modelo. {$this->primaryKey}", 1);
                }
                $class->{$key} = $att;
            }
            $return[] = $class;
        }
        return $return;
    }

    /**
     * .\Find Methods
     */

    /**
     * Insert Methods
     */

    public function insert($data = [])
    {
        $data = $this->serializeToSaveData($data);
        if (empty($data[$this->primaryKey])) {
            unset($data[$this->primaryKey]);
        } else {
            if (!in_array($this->primaryKey, $this->allowedFields)) {
                $this->allowedFields[] = $this->primaryKey;
            }
        }

        $invalidKeys = array_diff(array_keys($data), $this->allowedFields);
        if (!empty($invalidKeys)) {
            foreach ($invalidKeys as $key => $value) {
                unset($data[$value]);
            }
            // Se encontraron claves no permitidas
            // throw new \Exception("Las propiedades '".implode(', ', $invalidKeys)."' no se encuentran definidas en el modelo.", 1);
        }

        // Usar queryFactory en lugar de herencia
        $this->query = $this->queryFactory->insert($this->table, $data);
        return $this->insertID();
    }

    private function insertID()
    {
        $stmt = self::prepareSql();
        $this->insertID = $this->conn->lastInsertId() ?? null;
        return $this->insertID;
    }

    /**
     * .\Insert Methods
     */

    public function save($data = [])
    {
        $data = $this->serializeToSaveData($data);
        if (!in_array($this->primaryKey, array_keys($data))) {
            return $this->insert($data);
        }
        return $this->update($data);
    }

    /**
     * Update Methods
     */

    public function update($data = [], $pk = null)
    {
        $data = $this->serializeToSaveData($data);
        if (isset($data[$this->primaryKey])) {
            $pk = $data[$this->primaryKey];
            unset($data[$this->primaryKey]);
        }

        $invalidKeys = array_diff(array_keys($data), $this->allowedFields);
        if (!empty($invalidKeys)) {
            foreach ($invalidKeys as $key => $value) {
                unset($data[$value]);
            }
            // Se encontraron claves no permitidas
            // throw new \Exception("Las propiedades '".implode(', ', $invalidKeys)."' no se encuentran definidas en el modelo.", 1);
        }

        // Usar queryFactory en lugar de herencia
        $this->query = $this->queryFactory->update($this->table, $data);
        $this->TableIsSet = true;
        if (!is_null($pk)) {
            if (is_array($pk)) {
                $this->where($pk);
            } else {
                $this->query->where(field($this->primaryKey)->eq($pk));
            }
        }
        return $this->affectedRows();
    }

    private function affectedRows()
    {
        $stmt = self::prepareSql();
        $this->affectedRows = $stmt->rowCount() ?? null;
        return $this->affectedRows;
    }

    /**
     * .\Update Methods
     */

    /**
     * Delete Methods
     */

    public function delete($pk = null)
    {
        // Usar queryFactory en lugar de herencia
        $this->query = $this->queryFactory->delete($this->table);
        $this->TableIsSet = true;
        if (!is_null($pk)) {
            if (is_array($pk)) {
                $this->where($pk);
            } else {
                $this->query->where(field($this->primaryKey)->eq($pk));
            }
        }
        return $this->deletedRows();
    }

    private function deletedRows()
    {
        $stmt = self::prepareSql();
        $this->deletedRows = $stmt->rowCount() ?? null;
        return $this->deletedRows;
    }

    /**
     * .\Delete Methods
     */

    public function execute()
    {
        return $this->query->compile();
        $stmt = self::prepareSql();
        $this->deletedRows = $stmt->rowCount() ?? null;
        $this->affectedRows = $stmt->rowCount() ?? null;
        $this->insertID = $this->conn->lastInsertId() ?? null;
        $this->data = $stmt->fetchAll(CONSTANTES_PDO["AS_ARRAY"]);
    }

    public function query(string $sql, array $params = [], string $type = "AS_ARRAY")
    {
        if (empty($sql)) {
            throw new Exception("No existe un query sql para ejecutar", 1);
        }
        $this->str_query = [$sql, $params];
        $stmt = $this->prepareSql();
        if (strstr($sql, "INSERT")) {
            $this->insertID = $this->conn->lastInsertId() ?? null;
            return $this->insertID;
        } else if (in_array($sql, ["DELETE", "UPDATE"])) {
            $this->deletedRows = $this->affectedRows = $stmt->rowCount() ?? null;
            return $this->affectedRows;
        } else {
            $this->data = $stmt->fetchAll(CONSTANTES_PDO[$type]);
            return $this->data;
        }
    }

    public function init_transaction()
    {
        if (false === $this->in_transaction) {
            $this->conn->beginTransaction();
        }
        $this->in_transaction = true;
        return $this;
    }

    public function commit_transaction()
    {
        if (false !== $this->in_transaction) {
            $this->conn->commit();
        }
        $this->in_transaction = false;
        return $this;
    }

    public function rollback_transaction()
    {
        if (false !== $this->in_transaction) {
            $this->conn->rollBack();
        }
        $this->in_transaction = false;
        return $this;
    }

    private function prepareSql()
    {
        list($sql, $params) = $this->getSqlParams();
        $tipos = self::getTipos($params);
        try {
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $index => $param) {
                $stmt->bindValue(
                    $index + 1,
                    $param,
                    $tipos[$index]
                );
            }
            $stmt->execute();
        } catch (\PDOException $e) {
            if (false !== $this->in_transaction) {
                throw new \Exception("Error: {$e->getMessage()}");
            } else {
                vds("Error: {$e->getMessage()}");
            }
        } catch (\Exception $e) {
            if (false !== $this->in_transaction) {
                throw new \Exception("Error: {$e->getMessage()}");
            } else {
                vds("Error: {$e->getMessage()}");
            }
        }
        return $stmt;
    }

    private function getSqlParams()
    {
        if (!empty($this->query)) {
            $query = $this->query->compile();
            $this->query = null;
            $sql = $query->sql();
            $params = $query->params();
        } elseif ($this->str_query) {
            list($sql, $params) = $this->str_query;
        }
        return [$sql, $params];
    }

    private function getTipos(array $params): array
    {
        $tipos = [];
        foreach ($params as $param) {
            $tipos[] = TIPOS_PDO[gettype($param)] ?? TIPOS_PDO["string"];
        }
        return $tipos;
    }

    private function serializeToSaveData($data = []): array
    {
        if (is_object($data) && get_class($data) !== 'stdClass') {
            if (file_exists(base_dir(get_class($data)) . ".php")) {
                $data = $data->toArray();
            }
        }
        return (array)$data;
    }

    /**
     * Getter para acceder al QueryFactory si es necesario
     * (opcional, por si necesitas acceso directo)
     */
    public function getQueryFactory(): QueryFactory
    {
        return $this->queryFactory;
    }
}
