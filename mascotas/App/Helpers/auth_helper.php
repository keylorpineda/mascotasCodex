<?php
// App/Helpers/auth_helper.php

if (!function_exists("validar_permiso")) {
    /**
     * Valida si un usuario tiene permiso para ejecutar cierta acción.
     * En este proyecto está simplificado: siempre devuelve true.
     *
     * @param string $permiso
     * @return bool
     */
    function validar_permiso($permiso)
    {
        return true; // en producción aquí se haría la validación real
    }
}

if (!function_exists("is_logged_in")) {
    /**
     * Verifica si el usuario está logueado.
     * En este entorno de pruebas siempre devuelve true,
     * para que no bloquee las vistas Personas/Mascotas.
     *
     * @return bool
     */
    function is_logged_in()
    {
        return true; // en desarrollo dejamos acceso libre
    }
}
