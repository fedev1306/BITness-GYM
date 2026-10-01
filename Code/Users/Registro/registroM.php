<?php
include '../../config/conexion.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit();
}

$nombre_usuario = trim($_POST['nombre_usuario'] ?? '');
$correo = filter_input(INPUT_POST, 'correo', FILTER_VALIDATE_EMAIL);
$contrasena = $_POST['contrasena'] ?? '';

if ($nombre_usuario === '' || !$correo || $contrasena === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Datos de registro inválidos.']);
    exit();
}

if (strlen($contrasena) < 10) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'La contraseña debe tener al menos 10 caracteres.']);
    exit();
}

$query = $conn->prepare('SELECT 1 FROM USUARIO WHERE email = :correo LIMIT 1');
$query->execute([':correo' => $correo]);
if ($query->fetchColumn()) {
    http_response_code(409);
    echo json_encode(['status' => 'error', 'message' => 'El correo ya está registrado.']);
    exit();
}

$hash_password = password_hash($contrasena, PASSWORD_DEFAULT);
$fecha_registro = date('Y-m-d');

try {
    $conn->beginTransaction();

    $query = $conn->prepare("INSERT INTO USUARIO (email, contraseña, nombre, rol, fecha_registro) VALUES (:correo, :contrasena, :nombre_usuario, 'cliente', :fecha_registro)");
    $query->execute([
        ':correo' => $correo,
        ':contrasena' => $hash_password,
        ':nombre_usuario' => $nombre_usuario,
        ':fecha_registro' => $fecha_registro,
    ]);

    $id_usuario = $conn->lastInsertId();

    $conn->prepare('INSERT INTO PERFIL (id_usuario) VALUES (:id_usuario)')
        ->execute([':id_usuario' => $id_usuario]);
    $conn->prepare('INSERT INTO CLIENTE (id_usuario) VALUES (:id_usuario)')
        ->execute([':id_usuario' => $id_usuario]);

    $conn->commit();
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Registration failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'No se pudo completar el registro.']);
    exit();
}

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
session_regenerate_id(true);

$_SESSION['id_usuario'] = $id_usuario;
$_SESSION['usuario'] = $nombre_usuario;
$_SESSION['email'] = $correo;
$_SESSION['rol'] = 'cliente';

echo json_encode(['status' => 'success', 'message' => 'Registro exitoso']);
?>