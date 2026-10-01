<?php
include '../../config/conexion.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit();
}

$correo = filter_input(INPUT_POST, 'correo', FILTER_VALIDATE_EMAIL);
$contrasena = $_POST['contrasena'] ?? '';

if (!$correo || $contrasena === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Credenciales inválidas.']);
    exit();
}

$query = $conn->prepare('SELECT * FROM USUARIO WHERE email = :correo LIMIT 1');
$query->execute([':correo' => $correo]);
$usuario = $query->fetch();

$authenticated = false;
$needsUpgrade = false;

if ($usuario) {
    $storedHash = (string) $usuario['contraseña'];

    if (password_verify($contrasena, $storedHash)) {
        $authenticated = true;
        $needsUpgrade = password_needs_rehash($storedHash, PASSWORD_DEFAULT);
    } elseif (hash_equals($storedHash, hash('sha256', $contrasena))) {
        // Compatibilidad temporal con usuarios históricos. Se migra el hash al iniciar sesión.
        $authenticated = true;
        $needsUpgrade = true;
    }
}

if (!$authenticated) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Correo o contraseña incorrectos.']);
    exit();
}

if ($needsUpgrade) {
    $newHash = password_hash($contrasena, PASSWORD_DEFAULT);
    $update = $conn->prepare('UPDATE USUARIO SET contraseña = :hash WHERE id_usuario = :id');
    $update->execute([':hash' => $newHash, ':id' => $usuario['id_usuario']]);
}

$isSubscribed = false;
if ($usuario['rol'] === 'cliente') {
    $check = $conn->prepare('SELECT 1 FROM Paga WHERE id_usuario_FK = :id_usuario LIMIT 1');
    $check->execute([':id_usuario' => $usuario['id_usuario']]);
    $isSubscribed = (bool) $check->fetchColumn();
}

iniciarSesion($usuario, $isSubscribed);

function iniciarSesion(array $usuario, bool $isSubscribed): void
{
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

    $_SESSION['id_usuario'] = $usuario['id_usuario'];
    $_SESSION['usuario'] = $usuario['nombre'];
    $_SESSION['email'] = $usuario['email'];
    $_SESSION['rol'] = $usuario['rol'];

    echo json_encode([
        'status' => 'success',
        'rol' => $usuario['rol'],
        'subscribed' => $isSubscribed,
    ]);
}
?>