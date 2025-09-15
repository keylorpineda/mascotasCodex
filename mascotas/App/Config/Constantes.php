<?php
define('NOMBRE_EMPRESA', 'EMPRESA');

define('COOKIE_CONFIG', [
    'secure' => false, // Solo enviar la cookie a través de HTTPS === true
    'httponly' => true, // La cookie solo es accesible a través del protocolo HTTP, no JavaScript
    'samesite' => 'Strict', // Previene que la cookie sea enviada junto con solicitudes iniciadas por terceros
    'path' => '/', // La cookie es válida en todo el dominio
    'domain' => '', // Define tu dominio si es necesario
    'delete_time_delay' => 1, // Define el tiempo de espera en segundos
]);
define('TIPOS_PDO', [
    'boolean' => \PDO::PARAM_BOOL,
    'integer' => \PDO::PARAM_INT,
    'double'  => \PDO::PARAM_STR,
    'string'  => \PDO::PARAM_STR,
    'NULL'    => \PDO::PARAM_NULL,
]);
define('DRIVER_MYSQL',  'mysql');
define('DRIVER_SQLSRV', 'sqlsrv');
define('CONSTANTES_PDO', [
    "AS_ARRAY"  => \PDO::FETCH_ASSOC,
    "AS_OBJECT" => \PDO::FETCH_OBJ,
]);
define('BASE_URL', 'https://localhost/mascotas');
define('BASE_DIR', dirname(__FILE__, 3));

define('COOKIE_ID_USUARIO',      'mmulpto');
define('COOKIE_ID_PERSONA',      'pjbrv');
define('COOKIE_ID_TIPO_USUARIO', 'rtrvsr');

define('WRITER_DIR', dirname(__FILE__, 3)."\writer");
define('MAX_FILE_SIZE', 3 * 1024 * 1024); // Tamaño máximo de archivo en bytes (3 MB)
define('ALLOWED_FILE_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    "application/vnd.ms-excel.sheet.macroEnabled.12",
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'application/zip',
    'application/x-rar-compressed',
    'application/x-7z-compressed',
]);
define('ALLOWED_EXTENSIONS', [
    'jpg',
    'jpeg',
    'png',
    'gif',
    'pdf',
    'doc',
    'docx',
    'xls',
    'xlsx',
    'ppt',
    'pptx',
    'zip',
    'rar',
    '7z',
]);

define('RUTA_LOGO_EMPRESA', 'public/img/logo.png');

define('CAPTCHA_CLAVE_PRIVADA', "6LcmyhIrAAAAABbX1iL8aen-GjW_-tPpKdTa_xcJ");
define('CAPTCHA_RUTA_VALIDAR_TOKEN', 'https://www.google.com/recaptcha/api/siteverify');