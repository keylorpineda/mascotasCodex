<?php

namespace App\Config;

$routes = new \App\Core\RoutesEngine();

$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Inicio\Inicio');
$routes->setDefaultMethod('inicio');
$routes->setDebugMode(false);

//AQUÍ DEFINIR LAS RUTAS
$routes->get("/",      "Inicio\Inicio::inicio");
$routes->get("inicio", "Inicio\Inicio::inicio");

$routes->get("login",         "Usuarios\Usuarios::login");
$routes->post("login/validar", "Usuarios\Usuarios::validar_login");

$routes->get("usuarios",          "Usuarios\Usuarios::menu");
$routes->get("usuarios/listado",  "Usuarios\Usuarios::listado");
$routes->get("usuarios/permisos", "Usuarios\Permisos::listado");

$routes->get("personas/obtener",          "Personas\\Personas::obtener");
$routes->get("personas/buscar-por-cedula", "Personas\\Personas::buscar_por_cedula");
$routes->post("personas/guardar",          "Personas\\Personas::guardar");
$routes->post("personas/editar",           "Personas\\Personas::editar");
$routes->post("personas/eliminar",         "Personas\\Personas::remover");
$routes->get("personas/listado",          "Personas\\Personas::listado");

$routes->get("mascotas/obtener",  "Mascotas\\Mascotas::obtener");
$routes->post("mascotas/guardar",  "Mascotas\\Mascotas::guardar");
$routes->post("mascotas/editar",   "Mascotas\\Mascotas::editar");
$routes->post("mascotas/eliminar", "Mascotas\\Mascotas::remover");
$routes->get("mascotas/listado",  "Mascotas\\Mascotas::listado");


$routes->dispatch();

// ESTADÍSTICAS DEL ROUTER (para debugging)
if ($_ENV["environment"] !== "production") {
    $stats = $routes->getStats();
    $log = base_dir("writer/logs/routes.log");
    if (!is_dir(dirname($log))) {
        mkdir(dirname($log), 0755, true);
    }
    error_log("Router Stats: " . json_encode($stats) . PHP_EOL, 3, $log);
}
