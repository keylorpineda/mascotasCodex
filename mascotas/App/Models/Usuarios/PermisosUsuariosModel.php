<?php
namespace App\Models\Usuarios;

use App\Config\Model;
use App\Entities\Usuarios\PermisosUsuariosEntity;

class PermisosUsuariosModel extends Model
{
    protected string $table = "tpermisosusuarios";
    protected string $primaryKey = "ID_PERMISO";
    protected string $returnType = PermisosUsuariosEntity::class;

    protected array $allowedFields = [
        "ID_USUARIO",
        "PERMISO"
    ];
}