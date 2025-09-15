<?php
namespace App\Entities\Usuarios;

use App\Config\Entity;

class TiposUsuariosEntity extends Entity
{
    /**
     * @param mixed $ID_TIPO_USUARIO
     *
     * @return self
     */
    public function setIDTIPOUSUARIO($ID_TIPO_USUARIO)
    {
        if (!empty($ID_TIPO_USUARIO)) {
            $this->attributes["ID_TIPO_USUARIO"] = $ID_TIPO_USUARIO;
        }

        return $this;
    }

    /**
     * @param mixed $NOMBRE
     *
     * @return self
     */
    public function setNOMBRE($NOMBRE)
    {
        $this->attributes["NOMBRE"] = $NOMBRE;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIDTIPOUSUARIO()
    {
        return $this->attributes["ID_TIPO_USUARIO"];
    }

    /**
     * @return mixed
     */
    public function getNOMBRE()
    {
        return $this->attributes["NOMBRE"];
    }
}