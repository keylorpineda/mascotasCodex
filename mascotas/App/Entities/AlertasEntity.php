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
     * @param mixed $STATUS
     *
     * @return self
     */
    public function setSTATUS($STATUS)
    {
        $this->attributes["STATUS"] = (bool) $STATUS;

        return $this;
    }

    /**
     * @param mixed $DATA
     *
     * @return self
     */
    public function setDATA($DATA)
    {
        $this->attributes["DATA"] = $DATA;

        return $this;
    }

    /**
     * @param mixed $ERRORS
     *
     * @return self
     */
    public function setERRORS($ERRORS)
    {
        $this->attributes["ERRORS"] = $ERRORS;

        return $this;
    }

    /**
     * @param mixed $PROCESS
     *
     * @return self
     */
    public function setPROCESS($PROCESS)
    {
        $this->attributes["PROCESS"] = $PROCESS;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMENSAJE()
    {
        return $this->attributes["MENSAJE"] ?? '';
    }

    /**
     * @return mixed
     */
    public function getTITULO()
    {
        return $this->attributes["TITULO"] ?? '';
    }

    /**
     * @return mixed
     */
    public function getICONO()
    {
        return $this->attributes["ICONO"] ?? null;
    }

    /**
     * @return mixed
     */
    public function getTIPO()
    {
        return $this->attributes["TIPO"] ?? null;
    }

    /**
     * @return bool
     */
    public function getSTATUS(): bool
    {
        if (array_key_exists("STATUS", $this->attributes)) {
            return (bool) $this->attributes["STATUS"];
        }

        return strtoupper($this->getTIPO() ?? '') === 'SUCCESS';
    }

    /**
     * @return mixed
     */
    public function getDATA()
    {
        return $this->attributes["DATA"] ?? [];
    }

    /**
     * @return mixed
     */
    public function getERRORS()
    {
        return $this->attributes["ERRORS"] ?? [];
    }

    /**
     * @return mixed
     */
    public function getPROCESS()
    {
        return $this->attributes["PROCESS"] ?? null;
    }

    public function toArray()
    {
        $status  = $this->getSTATUS();
        $data    = $this->getDATA();
        $errors  = $this->getERRORS();
        $process = $this->getPROCESS();
        $mensaje = $this->getMENSAJE();
        $titulo  = $this->getTITULO();
        $tipo    = $this->getTIPO();
        $icono   = $this->getICONO();

        $payload = [
            'status'  => $status,
            'data'    => $data,
            'errors'  => $errors,
            'process' => $process,
            'message' => $mensaje,
            'type'    => $tipo !== null ? strtolower($tipo) : null,
            'title'   => $titulo,
        ];

        if ($icono !== null) {
            $payload['icon'] = strtolower($icono);
        }

        // Claves legacy para compatibilidad hacia atrás
        $payload['MENSAJE'] = $mensaje;
        $payload['TITULO']  = $titulo;
        $payload['ICONO']   = $icono;
        $payload['TIPO']    = $tipo;

        return $payload;
    }
}