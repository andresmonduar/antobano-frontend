<?php
session_name('ANTOBANO_CLIENTE'); session_start();
header('Content-Type: application/json; charset=utf-8');
require 'conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'Tu sesión expiró. Vuelve a iniciar sesión para pedir.']);
    exit;
}

$datos = json_decode(file_get_contents('php://input'), true);
$items = $datos['items'] ?? [];
$direccion = trim($datos['direccion'] ?? '');
$metodo_pago = trim($datos['metodo_pago'] ?? 'efectivo');
$id_cliente = (int) $_SESSION['id_usuario'];

if (empty($items)) {
    echo json_encode(['ok' => false, 'mensaje' => 'El carrito está vacío.']);
    exit;
}

if ($direccion === '') {
    echo json_encode(['ok' => false, 'mensaje' => 'Por favor, indica una dirección de entrega.']);
    exit;
}

// Calcular el total real desde los productos enviados
$total = 0;
foreach ($items as $item) {
    $total += ((float)$item['precio']) * ((int)$item['cantidad']);
}

// 1. Guardar el encabezado del pedido en la tabla pedidos
$stmtPedido = $conexion->prepare("INSERT INTO pedidos (id_cliente, direccion_entrega, metodo_pago, total) VALUES (?, ?, ?, ?)");
$stmtPedido->bind_param("issd", $id_cliente, $direccion, $metodo_pago, $total);

if (!$stmtPedido->execute()) {
    echo json_encode(['ok' => false, 'mensaje' => 'Error al registrar el pedido: ' . $stmtPedido->error]);
    exit;
}

$id_pedido_nuevo = $stmtPedido->insert_id;
$stmtPedido->close();

// 2. Guardar cada producto comprado en la tabla detalle_pedido
$stmtDetalle = $conexion->prepare("INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");

foreach ($items as $item) {
    $id_producto = (int) $item['id_producto'];
    $cantidad = (int) $item['cantidad'];
    $precio_unitario = (float) $item['precio'];
    $subtotal = $precio_unitario * $cantidad;

    $stmtDetalle->bind_param("iiidd", $id_pedido_nuevo, $id_producto, $cantidad, $precio_unitario, $subtotal);
    $stmtDetalle->execute();
}

$stmtDetalle->close();
$conexion->close();

echo json_encode([
    'ok' => true,
    'id_pedido' => $id_pedido_nuevo,
    'mensaje' => '¡Pedido #' . $id_pedido_nuevo . ' confirmado y guardado en la base de datos!'
]);
?>
