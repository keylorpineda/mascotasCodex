<?php
namespace App\Models\Personas;

use App\Config\Model;
use App\Entities\Personas\PersonasEntity;

class PersonasModel extends Model
{
    protected string $table = "tpersonas";
    protected string $primaryKey = "ID_PERSONA";
    protected string $returnType = PersonasEntity::class;

    protected array $allowedFields = [
        "ID_PERSONA",
        "NOMBRE",
        "TELEFONO",
        "CORREO",
        "ESTADO"
    ];
}
