<?php
require_once __DIR__ . "/config/conexion.php";

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    try {
        $sql = "SELECT p.id_producto, p.nombre, p.descripcion, p.imagen, p.precio, p.disponible, c.nombre AS categoria
                FROM productos p
                INNER JOIN categorias c ON p.id_categoria = c.id_categoria
                WHERE p.disponible = 1
                ORDER BY p.id_producto ASC";
        $productos = $pdo->query($sql)->fetchAll();

        http_response_code(200);
        echo json_encode([
            "exito" => true,
            "total" => count($productos),
            "productos" => $productos
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["exito" => false, "mensaje" => "Error al consultar el menú."]);
    }
    exit;
}

if ($metodo === 'POST') {
    $datos = json_decode(file_get_contents("php://input"), true);

    $id_categoria = $datos['id_categoria'] ?? 1;
    $nombre       = trim($datos['nombre'] ?? '');
    $descripcion  = trim($datos['descripcion'] ?? '');
    $precio       = $datos['precio'] ?? null;

    if ($nombre === '' || !is_numeric($precio) || $precio <= 0 || !is_numeric($id_categoria)) {
        http_response_code(400);
        echo json_encode(["exito" => false, "mensaje" => "id_categoria, nombre y precio mayor a 0 son obligatorios."]);
        exit;
    }

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO productos (id_categoria, nombre, descripcion, precio, disponible)
             VALUES (?, ?, ?, ?, 1)"
        );
        $stmt->execute([$id_categoria, $nombre, $descripcion !== '' ? $descripcion : null, $precio]);

        http_response_code(201);
        echo json_encode([
            "exito" => true,
            "mensaje" => "Producto agregado al menú.",
            "id_producto" => (int) $pdo->lastInsertId()
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["exito" => false, "mensaje" => "No se pudo guardar el producto. Verifica que id_categoria exista (1 a 6)."]);
    }
    exit;
}

http_response_code(405);
echo json_encode(["exito" => false, "mensaje" => "Método no permitido. Use GET o POST."]);
