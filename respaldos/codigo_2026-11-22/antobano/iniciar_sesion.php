<?php
header('Content-Type: application/json; charset=utf-8');
require 'conexion.php';

$correo = trim($_POST['correo'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($correo === '' or $password === '') {
    echo json_encode(['ok' => false, 'mensaje' => 'Por favor, escribe tu correo y contraseña.']);
    exit;
}

$consulta = $conexion->prepare("SELECT id_usuario, nombre, contrasena, rol FROM usuarios WHERE correo = ?");
$consulta->bind_param("s", $correo);
$consulta->execute();
$resultado = $consulta->get_result();

if ($fila = $resultado->fetch_assoc()) {
    if (password_verify($password, $fila['contrasena'])) {
        session_name('ANTOBANO_' . strtoupper($fila['rol']));
        session_set_cookie_params(86400);
        session_start();

        $_SESSION['id_usuario'] = $fila['id_usuario'];
        $_SESSION['usuario'] = $fila['nombre'];
        $_SESSION['rol'] = $fila['rol'];

        echo json_encode([
            'ok' => true,
            'usuario' => $fila['nombre'],
            'rol' => $fila['rol']
        ]);
    } else {
        echo json_encode(['ok' => false, 'mensaje' => 'Contraseña incorrecta. Inténtalo de nuevo.']);
    }
} else {
    echo json_encode(['ok' => false, 'mensaje' => 'No existe ninguna cuenta con ese correo.']);
}

$consulta->close();
$conexion->close();
?>
