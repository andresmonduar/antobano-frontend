<?php
require_once __DIR__ . "/config/conexion.php";

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    try {
        $sql = "SELECT p.id_pedido, u.nombre AS cliente, p.direccion_entrega, p.metodo_pago, p.estado, p.total, p.fecha_pedido
                FROM pedidos p
                INNER JOIN usuarios u ON p.id_cliente = u.id_usuario
                ORDER BY p.id_pedido DESC";
        $pedidos = $pdo->query($sql)->fetchAll();

        http_response_code(200);
        echo json_encode([
            "exito" => true,
            "total" => count($pedidos),
            "pedidos" => $pedidos
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["exito" => false, "mensaje" => "Error al listar los pedidos."]);
    }
    exit;
}

if ($metodo === 'POST') {
    $datos = json_decode(file_get_contents("php://input"), true);

    $id_cliente        = $datos['id_cliente'] ?? null;
    $direccion_entrega = trim($datos['direccion_entrega'] ?? '');
    $metodo_pago       = strtolower(trim($datos['metodo_pago'] ?? 'efectivo'));
    $items             = $datos['items'] ?? [];

    $metodos_validos = ['efectivo', 'nequi', 'daviplata', 'tarjeta'];

    if (!is_numeric($id_cliente) || $direccion_entrega === '' || !in_array($metodo_pago, $metodos_validos, true) || !is_array($items) || count($items) === 0) {
        http_response_code(400);
        echo json_encode([
            "exito" => false,
            "mensaje" => "id_cliente, direccion_entrega, metodo_pago válido y al menos un item son obligatorios."
        ]);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $total = 0;
        $stmtProd = $pdo->prepare("SELECT precio FROM productos WHERE id_producto = ? AND disponible = 1");
        $lineas = [];

        foreach ($items as $item) {
            $id_prod  = $item['id_producto'] ?? null;
            $cantidad = (int) ($item['cantidad'] ?? 0);

            if (!is_numeric($id_prod) || $cantidad <= 0) {
                throw new Exception("Item inválido en el pedido.");
            }

            $stmtProd->execute([$id_prod]);
            $prod = $stmtProd->fetch();
            if (!$prod) {
                throw new Exception("El producto ID $id_prod no existe o no está disponible.");
            }

            $subtotal = $prod['precio'] * $cantidad;
            $total += $subtotal;
            $lineas[] = [
                "id_producto" => $id_prod,
                "cantidad" => $cantidad,
                "precio_unitario" => $prod['precio'],
                "subtotal" => $subtotal
            ];
        }

        $stmtPedido = $pdo->prepare(
            "INSERT INTO pedidos (id_cliente, direccion_entrega, metodo_pago, estado, total)
             VALUES (?, ?, ?, 'pendiente', ?)"
        );
        $stmtPedido->execute([$id_cliente, $direccion_entrega, $metodo_pago, $total]);
        $id_pedido = (int) $pdo->lastInsertId();

        $stmtDetalle = $pdo->prepare(
            "INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario, subtotal)
             VALUES (?, ?, ?, ?, ?)"
        );
        foreach ($lineas as $linea) {
            $stmtDetalle->execute([
                $id_pedido,
                $linea['id_producto'],
                $linea['cantidad'],
                $linea['precio_unitario'],
                $linea['subtotal']
            ]);
        }

        $pdo->commit();

        http_response_code(201);
        echo json_encode([
            "exito" => true,
            "mensaje" => "Pedido registrado exitosamente.",
            "id_pedido" => $id_pedido,
            "total" => $total
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(400);
        echo json_encode(["exito" => false, "mensaje" => $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(["exito" => false, "mensaje" => "Método no permitido. Use GET o POST."]);
