<?php
namespace App\Core\Database;

use Latitude\QueryBuilder\Query\MySql\SelectQuery;
use Latitude\QueryBuilder\Query\MySql\InsertQuery;
use Latitude\QueryBuilder\Query\MySql\UpdateQuery;
use Latitude\QueryBuilder\Query\MySql\DeleteQuery;
use Latitude\QueryBuilder\Query\SqlServer\SelectQuery as SqlServerSelectQuery;
use Latitude\QueryBuilder\Engine\SqlServerEngine;
use Latitude\QueryBuilder\Builder\LikeBuilder;
use Latitude\QueryBuilder\Engine\MysqlEngine;
use Latitude\QueryBuilder\QueryFactory;

use function Latitude\QueryBuilder\on;
use function Latitude\QueryBuilder\alias;
use function Latitude\QueryBuilder\param;
use function Latitude\QueryBuilder\field;
use function Latitude\QueryBuilder\search;
use function Latitude\QueryBuilder\identifyAll;
use stdClass;

/**
 * Clase Model refactorizada manteniendo compatibilidad total
 * 
 * Esta refactorización mejora la organización interna sin cambiar la API pública
 */
class BaseModel extends QueryFactory implements QueryBuilderInterface, DatabaseInterface
{
    // Propiedades originales mantenidas para compatibilidad
    private $conn;
    private bool $in_transaction = false;
    private $query;
    private array $str_query = [];
    
    protected string $returnTypeDefault = "array";
    protected string $returnType = "array";
    protected array $data = [];
    protected array $allowedFields = [];
    protected string $view = "";
    protected string $table = "";
    protected string $primaryKey = "";
    
    public $insertID = null;
    public ?int $deletedRows = null;
    public ?int $affectedRows = null;
    
    private bool $TableIsSet = false;

    // Nuevas propiedades para la refactorización
    private ModelValidator $validator;
    private WhereClauseBuilder $whereBuilder;
    private JoinBuilder $joinBuilder;
    private ColumnProcessor $columnProcessor;
    private DatabaseConnection $dbConnection;

    public function __construct(?string $nombre_db = "default")
    {
        helper("database_helper");
        $ENGINE = [
            "mysql" => (new MysqlEngine()),
            "sqlsrv" => (new SqlServerEngine()),
        ];
        
        $this->conn = data_base($nombre_db);
        $driver = get_driver($nombre_db);
        parent::__construct($ENGINE[$driver]);
        
        // Inicializar componentes refactorizados
        $this->initializeComponents();
    }

    private function initializeComponents(): void
    {
        $this->validator = new ModelValidator($this->allowedFields, $this->primaryKey);
        $this->columnProcessor = new ColumnProcessor();
        $this->dbConnection = new DatabaseConnection($this->conn);
    }

    private function initializeQueryBuilders(): void
    {
        if (!$this->whereBuilder && $this->query) {
            $this->whereBuilder = new WhereClauseBuilder($this->query);
        }
        if (!$this->joinBuilder && $this->query) {
            $this->joinBuilder = new JoinBuilder($this->query);
        }
    }

    // Métodos públicos originales - mantenidos para compatibilidad exacta
    public function setQuery($query): self
    {
        $this->query = $query;
        $this->initializeQueryBuilders();
        return $this;
    }

    public function table(string $table, bool $resetType = false): self
    {
        $this->table = $table;
        if ($resetType) {
            $this->returnType = $this->returnTypeDefault;
        }
        $this->TableIsSet = true;
        $this->select("*");
        return $this;
    }

    public function view(): self
    {
        $this->table = $this->view;
        $this->returnType = $this->returnTypeDefault;
        $this->table($this->table);
        return $this;
    }

    public function toArray(): self
    {
        $this->returnType = $this->returnTypeDefault;
        return $this;
    }

    public function toObject(): self
    {
        $this->returnType = "stdClass";
        return $this;
    }

    // Métodos JOIN - refactorizados internamente
    public function inner_join(string $table, string $left, string $right): self
    {
        $this->initializeQueryBuilders();
        $this->joinBuilder->addJoin($table, $left, $right, 'INNER');
        $this->returnType = $this->returnTypeDefault;
        return $this;
    }

    public function left_join(string $table, string $left, string $right): self
    {
        $this->initializeQueryBuilders();
        $this->joinBuilder->addJoin($table, $left, $right, 'LEFT');
        $this->returnType = $this->returnTypeDefault;
        return $this;
    }

    public function right_join(string $table, string $left, string $right): self
    {
        $this->initializeQueryBuilders();
        $this->joinBuilder->addJoin($table, $left, $right, 'RIGHT');
        $this->returnType = $this->returnTypeDefault;
        return $this;
    }

    public function full_join(string $table, string $left, string $right): self
    {
        $this->initializeQueryBuilders();
        $this->joinBuilder->addJoin($table, $left, $right, 'FULL');
        $this->returnType = $this->returnTypeDefault;
        return $this;
    }

    // Métodos SELECT - mejorados internamente
    public function select(...$columns): self
    {
        if (!$this->TableIsSet) {
            $this->table($this->table);
        }
        
        $processedColumns = $this->columnProcessor->processColumns($columns);
        $this->setQuery($this->_select(...$processedColumns));
        $this->query->from($this->table);
        $this->initializeQueryBuilders();
        return $this;
    }

    // Métodos WHERE - refactorizados usando WhereClauseBuilder
    public function where($data, ?string $val = null): self
    {
        $this->ensureTableIsSet();
        $this->initializeQueryBuilders();
        $this->whereBuilder->buildWhere($data, $val, 'where');
        return $this;
    }

    public function andWhere($data, ?string $val = null): self
    {
        $this->ensureTableIsSet();
        $this->initializeQueryBuilders();
        $this->whereBuilder->buildWhere($data, $val, 'andWhere');
        return $this;
    }

    public function orWhere($data, ?string $val = null): self
    {
        $this->ensureTableIsSet();
        $this->initializeQueryBuilders();
        $this->whereBuilder->buildWhere($data, $val, 'orWhere');
        return $this;
    }

    public function whereNot($data, ?string $val = null): self
    {
        $this->ensureTableIsSet();
        $this->initializeQueryBuilders();
        $this->whereBuilder->buildWhereNot($data, $val, 'where');
        return $this;
    }

    public function andWhereNot($data, ?string $val = null): self
    {
        $this->ensureTableIsSet();
        $this->initializeQueryBuilders();
        $this->whereBuilder->buildWhereNot($data, $val, 'andWhere');
        return $this;
    }

    public function orWhereNot($data, ?string $val = null): self
    {
        $this->ensureTableIsSet();
        $this->initializeQueryBuilders();
        $this->whereBuilder->buildWhereNot($data, $val, 'orWhere');
        return $this;
    }

    public function whereIn($data, ?array $val = null): self
    {
        $this->ensureTableIsSet();
        $this->initializeQueryBuilders();
        $this->whereBuilder->buildWhereIn($data, $val, 'where');
        return $this;
    }

    public function andWhereIn($data, ?array $val = null): self
    {
        $this->ensureTableIsSet();
        $this->initializeQueryBuilders();
        $this->whereBuilder->buildWhereIn($data, $val, 'andWhere');
        return $this;
    }

    public function orWhereIn($data, ?array $val = null): self
    {
        $this->ensureTableIsSet();
        $this->initializeQueryBuilders();
        $this->whereBuilder->buildWhereIn($data, $val, 'orWhere');
        return $this;
    }

    public function like($data, ?string $val = null): self
    {
        $this->ensureTableIsSet();
        $this->initializeQueryBuilders();
        $this->whereBuilder->buildLike($data, $val, 'where');
        return $this;
    }

    public function andLike($data, ?string $val = null): self
    {
        $this->ensureTableIsSet();
        $this->initializeQueryBuilders();
        $this->whereBuilder->buildLike($data, $val, 'andWhere');
        return $this;
    }

    public function orLike($data, ?string $val = null): self
    {
        $this->ensureTableIsSet();
        $this->initializeQueryBuilders();
        $this->whereBuilder->buildLike($data, $val, 'orWhere');
        return $this;
    }

    // Métodos BETWEEN - mantenidos como estaban
    public function between(string $column, $start, $end): self
    {
        $this->ensureTableIsSet();
        $this->query->where(field($column)->between($start, $end));
        return $this;
    }

    public function orBetween(string $column, $start, $end): self
    {
        $this->ensureTableIsSet();
        $this->query->orWhere(field($column)->between($start, $end));
        return $this;
    }

    public function andBetween(string $column, $start, $end): self
    {
        $this->ensureTableIsSet();
        $this->query->andWhere(field($column)->between($start, $end));
        return $this;
    }

    // Métodos de ordenamiento y límites
    public function orderBy(string $col, string $direction = "asc"): self
    {
        $this->query->orderBy($col, strtolower($direction));
        return $this;
    }

    public function groupBy(string $col): self
    {
        $this->query->groupBy($col);
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->query->limit($limit);
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->query->offset($offset);
        return $this;
    }

    // Métodos de obtención de datos
    public function getFirstRow()
    {
        $data = $this->getAllRows();
        return array_shift($data);
    }

    public function getAllRows()
    {
        $this->ensureTableIsSet();
        if (!isset($this->query)) {
            throw new QueryException("No existe un query sql para ejecutar");
        }
        $stmt = $this->prepareSql();
        $this->data = $stmt->fetchAll(CONSTANTES_PDO["AS_ARRAY"]);
        return $this->toReturnType();
    }

    public function toReturnType()
    {
        $data = $this->data;
        if ($this->returnType === $this->returnTypeDefault) {
            return $data;
        }
        
        if (!class_exists($this->returnType)) {
            throw new ValidationException("La clase entidad '{$this->returnType}' no existe");
        }
        
        $return = [];
        foreach ($data as $value) {
            $class = new $this->returnType;
            foreach ($value as $key => $att) {
                if ($key != $this->primaryKey && !in_array($key, $this->allowedFields)) {
                    throw new ValidationException("La propiedad '{$key}' no se encuentra definida en el modelo");
                }
                $class->{$key} = $att;
            }
            $return[] = $class;
        }
        return $return;
    }

    // Métodos de modificación de datos - mejorados con validación
    public function insert($data = [])
    {
        $data = $this->serializeToSaveData($data);
        $data = $this->prepareDataForInsert($data);
        
        $this->query = $this->_insert($this->table, $data);
        return $this->insertID();
    }

    public function save($data = [])
    {
        $data = $this->serializeToSaveData($data);
        if (!in_array($this->primaryKey, array_keys($data))) {
            return $this->insert($data);
        }
        return $this->update($data);
    }

    public function update($data = [], $pk = null)
    {
        $data = $this->serializeToSaveData($data);
        $data = $this->prepareDataForUpdate($data, $pk);
        
        $this->query = $this->_update($this->table, $data['data']);
        $this->TableIsSet = true;
        
        if (!is_null($data['pk'])) {
            $this->addWhereConditionForPK($data['pk']);
        }
        
        return $this->affectedRows();
    }

    public function delete($pk = null)
    {
        $this->query = $this->_delete($this->table);
        $this->TableIsSet = true;
        
        if (!is_null($pk)) {
            $this->addWhereConditionForPK($pk);
        }
        
        return $this->deletedRows();
    }

    // Métodos de transacciones - mejorados
    public function init_transaction(): self
    {
        $this->dbConnection->beginTransaction();
        $this->in_transaction = $this->dbConnection->isInTransaction();
        return $this;
    }

    public function commit_transaction(): self
    {
        $this->dbConnection->commit();
        $this->in_transaction = $this->dbConnection->isInTransaction();
        return $this;
    }

    public function rollback_transaction(): self
    {
        $this->dbConnection->rollback();
        $this->in_transaction = $this->dbConnection->isInTransaction();
        return $this;
    }

    // Métodos de ejecución - mejorados con mejor manejo de errores
    public function execute()
    {
        return $this->query->compile();
    }

    public function query(string $sql, array $params = [], string $type = "AS_ARRAY")
    {
        if (empty($sql)) {
            throw new QueryException("No existe un query sql para ejecutar");
        }
        
        $this->str_query = [$sql, $params];
        $stmt = $this->prepareSql();
        
        return $this->handleQueryResult($sql, $stmt, $type);
    }

    // Métodos privados de soporte - refactorizados y mejorados
    private function ensureTableIsSet(): void
    {
        if (!$this->TableIsSet) {
            $this->select("*");
        }
    }

    private function prepareDataForInsert(array $data): array
    {
        // Actualizar validador con campos actuales
        $this->validator = new ModelValidator($this->allowedFields, $this->primaryKey);
        
        if (empty($data[$this->primaryKey])) {
            unset($data[$this->primaryKey]);
        } else {
            if (!in_array($this->primaryKey, $this->allowedFields)) {
                $this->allowedFields[] = $this->primaryKey;
                $this->validator = new ModelValidator($this->allowedFields, $this->primaryKey);
            }
        }

        return $this->validator->filterAllowedFields($data);
    }

    private function prepareDataForUpdate(array $data, $pk): array
    {
        // Actualizar validador con campos actuales
        $this->validator = new ModelValidator($this->allowedFields, $this->primaryKey);
        
        if (isset($data[$this->primaryKey])) {
            $pk = $data[$this->primaryKey];
            unset($data[$this->primaryKey]);
        }

        $filteredData = $this->validator->filterAllowedFields($data);
        
        return ['data' => $filteredData, 'pk' => $pk];
    }

    private function addWhereConditionForPK($pk): void
    {
        if (is_array($pk)) {
            $this->where($pk);
        } else {
            $this->query->where(field($this->primaryKey)->eq($pk));
        }
    }

    private function insertID()
    {
        $stmt = $this->prepareSql();
        $this->insertID = $this->dbConnection->getLastInsertId();
        return $this->insertID;
    }

    private function affectedRows()
    {
        $stmt = $this->prepareSql();
        $this->affectedRows = $stmt->rowCount() ?? null;
        return $this->affectedRows;
    }

    private function deletedRows()
    {
        $stmt = $this->prepareSql();
        $this->deletedRows = $stmt->rowCount() ?? null;
        return $this->deletedRows;
    }

    private function handleQueryResult(string $sql, $stmt, string $type)
    {
        if (strstr($sql, "INSERT")) {
            $this->insertID = $this->dbConnection->getLastInsertId();
            return $this->insertID;
        } elseif (strstr($sql, "DELETE") || strstr($sql, "UPDATE")) {
            $this->deletedRows = $this->affectedRows = $stmt->rowCount() ?? null;
            return $this->affectedRows;
        } else {
            $this->data = $stmt->fetchAll(CONSTANTES_PDO[$type]);
            return $this->data;
        }
    }

    private function prepareSql()
    {
        [$sql, $params] = $this->getSqlParams();
        $tipos = $this->getTipos($params);
        
        try {
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $index => $param) {
                $stmt->bindValue($index + 1, $param, $tipos[$index]);
            }
            $stmt->execute();
            return $stmt;
        } catch (\PDOException $e) {
            $this->handleSqlException($e);
        } catch (\Exception $e) {
            $this->handleSqlException($e);
        }
    }

    private function handleSqlException(\Exception $e): void
    {
        if ($this->dbConnection->isInTransaction()) {
            throw new DatabaseException("Error: {$e->getMessage()}");
        } else {
            vds("Error: {$e->getMessage()}");
        }
    }

    private function getSqlParams(): array
    {
        if (!empty($this->query)) {
            $query = $this->query->compile();
            $this->query = null;
            return [$query->sql(), $query->params()];
        } elseif ($this->str_query) {
            return $this->str_query;
        }
        
        throw new QueryException("No se puede obtener SQL: no hay query disponible");
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
}