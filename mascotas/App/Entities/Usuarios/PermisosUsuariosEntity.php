<?php
namespace App\Entities\Usuarios;

use App\Config\Entity;

class PermisosUsuariosEntity extends Entity
{
    /**
     * @param mixed $ID_PERMISO
     *
     * @return self
     */
    public function setIDPERMISO($ID_PERMISO)
    {
        $this->attributes["ID_PERMISO"] = $ID_PERMISO;

        return $this;
    }

    /**
     * @param mixed $ID_USUARIO
     *
     * @return self
     */
    public function setIDUSUARIO($ID_USUARIO)
    {
        $this->attributes["ID_USUARIO"] = $ID_USUARIO;

        return $this;
    }

    /**
     * @param mixed $PERMISO
     *
     * @return self
     */
    public function setPERMISO($PERMISO)
    {
        $this->attributes["PERMISO"] = $PERMISO;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIDPERMISO()
    {
        return $this->attributes["ID_PERMISO"];
    }

    /**
     * @return mixed
     */
    public function getIDUSUARIO()
    {
        return $this->attributes["ID_USUARIO"];
    }

    /**
     * @return mixed
     */
    public function getPERMISO()
    {
        return $this->attributes["PERMISO"];
    }
}