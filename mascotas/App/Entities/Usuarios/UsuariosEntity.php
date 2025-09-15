<?php
namespace App\Entities\Usuarios;

use App\Config\Entity;

class UsuariosEntity extends Entity
{
    public function validar_contrasenna(string $CONTRASENNA)
    {
        return password_verify($CONTRASENNA, $this->attributes["CONTRASENNA"]);
    }

    /**
     * @param mixed $ID_USUARIO
     *
     * @return self
     */
    public function setIDUSUARIO($ID_USUARIO)
    {
        if (!empty($ID_USUARIO)) {
            $this->attributes["ID_USUARIO"] = $ID_USUARIO;
        }

        return $this;
    }

    /**
     * @param mixed $ID_TIPO
     *
     * @return self
     */
    public function setIDTIPO($ID_TIPO)
    {
        $this->attributes["ID_TIPO"] = $ID_TIPO;

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
     * @param mixed $USUARIO
     *
     * @return self
     */
    public function setUSUARIO($USUARIO)
    {
        $this->attributes["USUARIO"] = $USUARIO;

        return $this;
    }

    /**
     * @param mixed $CONTRASENNA
     *
     * @return self
     */
    public function setCONTRASENNA($CONTRASENNA)
    {
        $this->attributes["CONTRASENNA"] = $CONTRASENNA;

        return $this;
    }

    /**
     * @param mixed $ESTADO
     *
     * @return self
     */
    public function setESTADO($ESTADO)
    {
        $this->attributes["ESTADO"] = $ESTADO;

        return $this;
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
    public function getIDTIPO()
    {
        return $this->attributes["ID_TIPO"];
    }

    /**
     * @return mixed
     */
    public function getNOMBRE()
    {
        return $this->attributes["NOMBRE"];
    }

    /**
     * @return mixed
     */
    public function getUSUARIO()
    {
        return $this->attributes["USUARIO"];
    }

    /**
     * @return mixed
     */
    public function getCONTRASENNA()
    {
        return $this->attributes["CONTRASENNA"];
    }

    /**
     * @return mixed
     */
    public function getESTADO()
    {
        return $this->attributes["ESTADO"];
    }
}