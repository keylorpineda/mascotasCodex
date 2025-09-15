<?php
namespace App\Entities;

use App\Config\Entity;

class AlertasEntity extends Entity
{
    /**
     * @param mixed $MENSAJE
     *
     * @return self
     */
    public function setMENSAJE($MENSAJE)
    {
        $this->attributes["MENSAJE"] = $MENSAJE;

        return $this;
    }

    /**
     * @param mixed $TITULO
     *
     * @return self
     */
    public function setTITULO($TITULO)
    {
        $this->attributes["TITULO"] = $TITULO;

        return $this;
    }

    /**
     * @param mixed $ICONO
     *
     * @return self
     */
    public function setICONO($ICONO)
    {
        $this->attributes["ICONO"] = $ICONO;

        return $this;
    }

    /**
     * @param mixed $TIPO
     *
     * @return self
     */
    public function setTIPO($TIPO)
    {
        $this->attributes["TIPO"] = $TIPO;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMENSAJE()
    {
        return $this->attributes["MENSAJE"];
    }

    /**
     * @return mixed
     */
    public function getTITULO()
    {
        return $this->attributes["TITULO"];
    }

    /**
     * @return mixed
     */
    public function getICONO()
    {
        return $this->attributes["ICONO"];
    }

    /**
     * @return mixed
     */
    public function getTIPO()
    {
        return $this->attributes["TIPO"];
    }
}