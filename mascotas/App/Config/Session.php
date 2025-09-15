<?php

namespace App\Config;

class Session
{
    public function __construct()
    {
        // Comprueba si no hay una sesión activa y, en ese caso, inicia una nueva sesión.
        if (!session_id()) { session_start(); }
    }

    public function set($data, $value = null, bool $temporary = false): void
    {
        if (false !== $temporary) {
            if (!is_string($data)) {
                throw new \Exception("Para asignar una sesión temporal, es requerido indicar una clave y un valor (no array)", 1);
            }
            $data .= "_tmp";
        }
        // Verifica si los datos pasados son un array.
        // Si es un array, fusiona los datos existentes en $_SESSION con los nuevos datos.
        // Si no es un array, asigna el valor a la clave especificada en $_SESSION.
        if (is_array($data)) {
            $_SESSION = array_merge($_SESSION, $data);
        } else {
            $_SESSION[$data] = $value;
        }
    }

    public function get(?string $key = null)
    {
        // Verifica si se especificó una clave.
        // Si se especificó una clave y existe en $_SESSION, y su valor no es nulo, devuelve el valor.
        if (!empty($key)) {
            if (array_key_exists($key."_tmp", $_SESSION) && ($value = $_SESSION[$key."_tmp"]) !== null) {
                $this->delete($key."_tmp");
                return $value;
            }
            if (array_key_exists($key, $_SESSION) && ($value = $_SESSION[$key]) !== null) {
                return $value;
            }
        }

        // Si $_SESSION está vacío, devuelve un array vacío si no se especificó una clave o null si se especificó una clave.
        if (empty($_SESSION)) {
            return $key === null ? [] : null;
        }

        // Si no se encontró la clave o no se especificó una clave, devuelve null.
        return null;
    }

    public function delete($data): void
    {
        // Verifica si los datos pasados son un array.
        // Si es un array, verifica si todas las claves existen en $_SESSION.
        // Si alguna clave no existe, lanza una excepción indicando las claves faltantes.
        // Luego, elimina las claves y sus valores correspondientes de $_SESSION.
        if (is_array($data)) {
            $missingKeys = array_diff($data, array_keys($_SESSION));
            if (!empty($missingKeys)) {
                throw new \Exception("Las siguientes claves no existen en las sesiones activas: " . implode(', ', $missingKeys), 1);
            }
            $_SESSION = array_diff_key($_SESSION, array_flip($data));
        } else {
            // Si los datos no son un array, verifica si la clave existe en $_SESSION.
            // Si la clave no existe, lanza una excepción indicando que la clave no existe.
            // Luego, elimina la clave y su valor correspondiente de $_SESSION.
            if (!array_key_exists($data, $_SESSION)) {
                // throw new \Exception("La clave especificada {$data} no existe en las sesiones activas", 1);
            }
            unset($_SESSION[$data]);
        }
    }
}