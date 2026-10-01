<?php
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_NAME') ?: 'BITness_GYM';
$username = getenv('DB_USER') ?: 'gymbro1314';
$password = getenv('DB_PASSWORD') ?: 'raccoon1331';

if ($username === '') {
    error_log('Database configuration is incomplete: DB_USER is missing.');
    http_response_code(500);
    exit('Error de configuración del servidor.');
}

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $conn = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo conectar con la base de datos.');
}
?>
