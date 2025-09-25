<?php

use App\Entities\AlertasEntity;

if (!function_exists("Success")) {
        function Success(string $mensaje, string $titulo = "Correcto"): AlertasEntity
        {
                return new AlertasEntity([
                        "MENSAJE" => $mensaje,
                        "TITULO"  => $titulo,
                        "ICONO"   => "SUCCESS",
                        "TIPO"    => "SUCCESS",
                        "STATUS"  => true,
                ]);
        }
}

if (!function_exists("Danger")) {
        function Danger(string $mensaje, string $titulo = "Error"): AlertasEntity
        {
                return new AlertasEntity([
                        "MENSAJE" => $mensaje,
                        "TITULO"  => $titulo,
                        "ICONO"   => "DANGER",
                        "TIPO"    => "DANGER",
                        "STATUS"  => false,
                ]);
        }
}

if (!function_exists("Info")) {
        function Info(string $mensaje, string $titulo = "Atención"): AlertasEntity
        {
                return new AlertasEntity([
                        "MENSAJE" => $mensaje,
                        "TITULO"  => $titulo,
                        "ICONO"   => "INFO",
                        "TIPO"    => "INFO",
                        "STATUS"  => true,
                ]);
        }
}

if (!function_exists("Warning")) {
        function Warning(string $mensaje, string $titulo = "Atención!"): AlertasEntity
        {
                return new AlertasEntity([
                        "MENSAJE" => $mensaje,
                        "TITULO"  => $titulo,
                        "ICONO"   => "WARNING",
                        "TIPO"    => "WARNING",
                        "STATUS"  => false,
                ]);
        }
}

