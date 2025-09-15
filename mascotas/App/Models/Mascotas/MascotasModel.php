<?php
namespace App\Models\Mascotas;

use App\Config\Model;
use App\Entities\Mascotas\MascotasEntity;

class MascotasModel extends Model
{
    protected string $table = "tmascotas";
    protected string $primaryKey = "ID_MASCOTA";
    protected string $returnType = MascotasEntity::class;

    protected array $allowedFields = [
        "ID_PERSONA",
        "NOMBRE_MASCOTA",
        "FOTO_URL",
        "ESTADO"
    ];
}
