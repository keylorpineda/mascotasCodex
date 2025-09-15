<?php
namespace App\Models\Usuarios;

use App\Config\Model;
use App\Entities\Usuarios\TiposUsuariosEntity;

class TiposUsuariosModel extends Model
{
    protected string $table = "ttiposusuarios";
    protected string $primaryKey = "ID_TIPO_USUARIO";
    protected string $returnType = TiposUsuariosEntity::class;

    protected array $allowedFields = [
        "NOMBRE"
    ];
}