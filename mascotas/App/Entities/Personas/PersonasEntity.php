<?php

namespace App\Entities\Personas;

use App\Config\Entity;

class PersonasEntity extends Entity
{
    /** @return self */
    public function setIDPERSONA($v)
    {
        if ($v !== '') $this->attributes["ID_PERSONA"] = $v;
        return $this;
    }
    /** @return self */
    public function setNOMBRE($v)
    {
        $this->attributes["NOMBRE"] = $v;
        return $this;
    }
    /** @return self */
    public function setTELEFONO($v)
    {
        $this->attributes["TELEFONO"] = $v;
        return $this;
    }
    /** @return self */
    public function setCORREO($v)
    {
        $this->attributes["CORREO"] = $v;
        return $this;
    }
    /** @return mixed */
    public function getIDPERSONA()
    {
        return $this->attributes["ID_PERSONA"] ?? null;
    }
    /** @return mixed */
    public function getNOMBRE()
    {
        return $this->attributes["NOMBRE"] ?? null;
    }
    /** @return mixed */
    public function getTELEFONO()
    {
        return $this->attributes["TELEFONO"] ?? null;
    }
    /** @return mixed */
    public function getCORREO()
    {
        return $this->attributes["CORREO"] ?? null;
    }
}
