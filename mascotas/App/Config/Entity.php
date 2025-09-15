<?php

namespace App\Config;

class Entity
{
    protected array $attributes = [];

    public function __construct(?array $data = null)
    {
        if (!is_array($data)) { return $this; }
        foreach ($data as $key => $value) {
            $this->__set($key, $value);
        }
    }

    public function toArray()
    {
        $keys = array_filter(
            array_keys(
                $this->attributes
            ),
            static fn ($key) => strpos($key, '_') !== 0
        );
        return array_intersect_key(
            $this->attributes,
            array_flip($keys)
        );
    }

    public function toStdClass()
    {
        return (object)self::toArray();
    }

    public function __get($key)
    {
        $result = null;

        // Convertir a CamelCase para el método
        $method = 'get' . str_replace(
            [ '-', '_' ],
            '',
            ucwords( $key, "-_" )
        );

        // Si existe un método get* para esta clave,
        // utilizar ese método para insertar este valor.
        if (method_exists($this, $method)) {
            $result = $this->{$method}();
        }

        // De lo contrario, devolver la propiedad protegida
        // si existe.
        elseif (array_key_exists($key, $this->attributes)) {
            $result = $this->attributes[$key];
        }

        return $result;
    }

    public function __set(string $key, $value = null)
    {
        // si existe un método set* para esta clave, utiliza ese método
        // para insertar este valor. debe estar fuera de la comprobación $isNullable,
        // por lo que tal vez quiera hacer algo con el valor nulo automáticamente
        $method = 'set' . str_replace(
            [ '-', '_' ],
            '',
            ucwords( $key, "-_" )
        );
        if (method_exists($this, $method)) {
            $this->{$method}($value);
            return $this;
        }
        // De lo contrario, simplemente asigna el valor. Esto permite la creación de nuevas
        // propiedades de clase que no están definidas, aunque no se pueden
        // guardar. Útil para obtener valores a través de uniones, asignar
        // relaciones, etc.
        $this->attributes[$key] = $value;
        return $this;
    }
}