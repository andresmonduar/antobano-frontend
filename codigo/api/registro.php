<?php
require_once __DIR__ . "/config/conexion.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["exito" => false, "mensaje" => "Método no permitido. Use POST."]);
    exit;
}

$datos = json_decode(file_get_contents("php://input"), true);

$nombre     = trim($datos['nombre'] ?? '');
$correo     = strtolower(trim($datos['correo'] ?? ''));
$telefono   = trim($datos['telefono'] ?? '');
$direccion  = trim($datos['direccion_principal'] ?? '');
$contrasena = $datos['contrasena'] ?? '';

if ($nombre === '' || $correo === '' || strlen($contrasena) < 6) {
    http_response_code(400);
    echo json_encode([
        "exito" => false,
        "mensaje" => "Nombre, correo válido y contraseña (mínimo 6 caracteres) son obligatorios."
    ]);
    exit;
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["exito" => false, "mensaje" => "El formato del correo no es válido."]);
    exit;
}

try {
    $verificar = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE correo = ?");
    $verificar->execute([$correo]);

    if ($verificar->fetch()) {
        http_response_code(409);
        echo json_encode(["exito" => false, "mensaje" => "El correo ya está registrado."]);
        exit;
    }

    $hash = password_hash($contrasena, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare(
        "INSERT INTO usuarios (nombre, correo, telefono, contrasena, direccion_principal, rol)
         VALUES (?, ?, ?, ?, ?, 'cliente')"
    );
    $stmt->execute([
        $nombre,
        $correo,
        $telefono !== '' ? $telefono : null,
        $hash,
        $direccion !== '' ? $direccion : null
    ]);

    http_response_code(201);
    echo json_encode([
        "exito" => true,
        "mensaje" => "Cliente registrado correctamente.",
        "id_usuario" => (int) $pdo->lastInsertId()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["exito" => false, "mensaje" => "No se pudo completar el registro."]);
}
