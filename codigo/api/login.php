<?php
require_once __DIR__ . "/config/conexion.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["exito" => false, "mensaje" => "Método no permitido. Use POST."]);
    exit;
}

$datos = json_decode(file_get_contents("php://input"), true);

$correo     = strtolower(trim($datos['correo'] ?? ''));
$contrasena = $datos['contrasena'] ?? '';

if ($correo === '' || $contrasena === '') {
    http_response_code(400);
    echo json_encode(["exito" => false, "mensaje" => "Debe enviar correo y contraseña."]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id_usuario, nombre, correo, contrasena, rol FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($contrasena, $usuario['contrasena'])) {
        http_response_code(401);
        echo json_encode(["exito" => false, "mensaje" => "Credenciales incorrectas."]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        "exito" => true,
        "mensaje" => "Autenticación satisfactoria.",
        "usuario" => [
            "id_usuario" => (int) $usuario['id_usuario'],
            "nombre" => $usuario['nombre'],
            "correo" => $usuario['correo'],
            "rol" => $usuario['rol']
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["exito" => false, "mensaje" => "Error interno al iniciar sesión."]);
}
