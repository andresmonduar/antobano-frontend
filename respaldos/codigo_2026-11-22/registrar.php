<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require 'conexion.php';

$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($nombre === '' or $apellido === '' or $correo === '' or $password === '') {
    echo json_encode(['ok' => false, 'mensaje' => 'Todos los campos son obligatorios.']);
    exit;
}

$consulta = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE correo = ?");
$consulta->bind_param("s", $correo);
$consulta->execute();
$consulta->store_result();

if ($consulta->num_rows > 0) {
    echo json_encode(['ok' => false, 'mensaje' => 'Este correo ya está registrado. Intenta iniciar sesión.']);
    $consulta->close();
    exit;
}
$consulta->close();

$nombre_completo = $nombre . ' ' . $apellido;
$contrasena_segura = password_hash($password, PASSWORD_DEFAULT);
$rol = 'cliente';

$insertar = $conexion->prepare("INSERT INTO usuarios (nombre, correo, contrasena, rol) VALUES (?, ?, ?, ?)");
$insertar->bind_param("ssss", $nombre_completo, $correo, $contrasena_segura, $rol);

if ($insertar->execute()) {
    $_SESSION['id_usuario'] = $insertar->insert_id;
    $_SESSION['usuario'] = $nombre_completo;
    $_SESSION['rol'] = $rol;
    echo json_encode(['ok' => true, 'usuario' => $nombre_completo]);
} else {
    echo json_encode(['ok' => false, 'mensaje' => 'Error al guardar: ' . $insertar->error]);
}

$insertar->close();
$conexion->close();
?>
