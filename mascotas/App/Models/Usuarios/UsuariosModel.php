<?php
namespace App\Models\Usuarios;

use App\Config\Model;
use App\Entities\Usuarios\UsuariosEntity;

class UsuariosModel extends Model
{
    protected string $table = "tusuarios";
    protected string $primaryKey = "ID_USUARIO";
    protected string $returnType = UsuariosEntity::class;

    protected array $allowedFields = [
        "ID_TIPO",
        "NOMBRE",
        "USUARIO",
        "CONTRASENNA",
        "ESTADO",
    ];
}