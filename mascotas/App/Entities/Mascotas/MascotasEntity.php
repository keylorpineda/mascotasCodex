<?php

namespace App\Entities\Mascotas;

use App\Config\Entity;

class MascotasEntity extends Entity
{
    public function setIDMASCOTA($v)
    {
        if ($v !== '') $this->attributes["ID_MASCOTA"] = $v;
        return $this;
    }
    public function setIDPERSONA($v)
    {
        if ($v !== '') $this->attributes["ID_PERSONA"] = $v;
        return $this;
    } // cédula
    public function setNOMBREMASCOTA($v)
    {
        $this->attributes["NOMBRE_MASCOTA"] = $v;
        return $this;
    }
    public function setFOTOURL($v)
    {
        $this->attributes["FOTO_URL"] = $v;
        return $this;
    }
    public function setESTADO($v)
    {
        $this->attributes["ESTADO"] = $v;
        return $this;
    } // 1 / 0

    public function getIDMASCOTA()
    {
        return $this->attributes["ID_MASCOTA"] ?? null;
    }
    public function getIDPERSONA()
    {
        return $this->attributes["ID_PERSONA"] ?? null;
    }
    public function getNOMBREMASCOTA()
    {
        return $this->attributes["NOMBRE_MASCOTA"] ?? null;
    }
    public function getFOTOURL()
    {
        return $this->attributes["FOTO_URL"] ?? null;
    }
    public function getESTADO()
    {
        return $this->attributes["ESTADO"] ?? null;
    }
}
