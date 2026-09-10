<?php

declare(strict_types=1);

// Configuración centralizada de conexión a la Base de Datos KION

$dbHost = 'localhost';
$dbName = 'kion';
$dbUser = 'root';
$dbPass = '';

$pdo = null;
$errorConexion = null;

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    $errorConexion = 'No pudimos conectar con la base de datos.';
    error_log('KION conexion.php - Error de conexión: ' . $e->getMessage());
}
