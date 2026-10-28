<?php
// config/bd.php — Datos de conexión. Se leen de variables de entorno (así no se escriben claves en el código);
// los valores por defecto sirven para el docker-compose.yml del laboratorio.
declare(strict_types=1);

return [
    'host' => getenv('BD_HOST') ?: '127.0.0.1',
    'puerto' => (int) (getenv('BD_PUERTO') ?: 3307),
    'base' => getenv('BD_NOMBRE') ?: 'apicultores',
    'usuario' => getenv('BD_USUARIO') ?: 'apicultores',
    'clave' => getenv('BD_CLAVE') ?: 'miel2026',
];
