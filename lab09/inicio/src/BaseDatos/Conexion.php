<?php
// src/BaseDatos/Conexion.php — Crea la conexión PDO con las opciones correctas (una sola vez por petición).
declare(strict_types=1);

namespace Apicultores\BaseDatos;

use PDO;

final class Conexion
{
    public static function crear(array $config): PDO
    {
        // charset=utf8mb4 en el DSN: tildes, ñ y emojis viajan bien en ambos sentidos
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $config['host'], $config['puerto'], $config['base']);
        return new PDO($dsn, $config['usuario'], $config['clave'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // todo error de SQL es una excepción (PDOException)
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,   // filas como arreglos asociativos
            PDO::ATTR_EMULATE_PREPARES => false,                // consultas preparadas REALES en el servidor MySQL
        ]);
    }
}
