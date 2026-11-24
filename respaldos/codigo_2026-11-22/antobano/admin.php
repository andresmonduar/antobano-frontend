<?php
session_name('ANTOBANO_ADMINISTRADOR'); session_start();
require 'conexion.php';

// Guardia de seguridad: solo permite entrar al rol administrador
if (!isset($_SESSION['usuario']) or ($_SESSION['rol'] ?? '') !== 'administrador') {
    header("Location: login.html");
    exit;
}

$nombre_admin = htmlspecialchars($_SESSION['usuario']);
$mensaje_alerta = '';

// Guardar cambios cuando el administrador presiona "Guardar cambios" en un pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' and isset($_POST['id_pedido'])) {
    $id_pedido = (int) $_POST['id_pedido'];
    $nuevo_estado = $_POST['estado'] ?? 'pendiente';
    $id_dom = $_POST['id_domiciliario'] !== '' ? (int) $_POST['id_domiciliario'] : null;

    if ($id_dom === null) {
        $stmtUpd = $conexion->prepare("UPDATE pedidos SET estado = ?, id_domiciliario = NULL WHERE id_pedido = ?");
        $stmtUpd->bind_param("si", $nuevo_estado, $id_pedido);
    } else {
        $stmtUpd = $conexion->prepare("UPDATE pedidos SET estado = ?, id_domiciliario = ? WHERE id_pedido = ?");
        $stmtUpd->bind_param("sii", $nuevo_estado, $id_dom, $id_pedido);
    }

    if ($stmtUpd->execute()) {
        header("Location: admin.php?guardado=" . $id_pedido); exit;
    }
    $stmtUpd->close();
}

// 1. Obtener lista de domiciliarios disponibles
$domiciliarios = [];
$resDom = $conexion->query("SELECT id_usuario, nombre FROM usuarios WHERE rol = 'domiciliario'");
if ($resDom) {
    while ($d = $resDom->fetch_assoc()) {
        $domiciliarios[] = $d;
    }
}

// 2. Obtener todos los pedidos con el nombre del cliente
$pedidos = [];
$sqlPedidos = "SELECT p.*, u.nombre AS nombre_cliente 
               FROM pedidos p 
               INNER JOIN usuarios u ON p.id_cliente = u.id_usuario 
               ORDER BY p.id_pedido DESC";
$resPed = $conexion->query($sqlPedidos);

if ($resPed) {
    while ($ped = $resPed->fetch_assoc()) {
        $id_p = (int) $ped['id_pedido'];
        $ped['platos'] = [];
        $resDet = $conexion->query("SELECT d.cantidad, p.nombre FROM detalle_pedido d INNER JOIN productos p ON d.id_producto = p.id_producto WHERE d.id_pedido = $id_p");
        while ($det = $resDet->fetch_assoc()) {
            $ped['platos'][] = $det;
        }
        $pedidos[] = $ped;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="refresh" content="10">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AntoBano - Panel Administrador</title>
    <link rel="stylesheet" href="style.css?v=9">
</head>
<body>

    <header class="navbar">
        <a href="admin.php" class="logo">
            <span class="icono-burger">🍔</span>
            <h2>AntoBano Admin</h2>
        </a>

        <nav class="nav-links">
            <a href="admin.php" class="activo">Pedidos Entrantes (<?php echo count($pedidos); ?>)</a>
        </nav>

        <div class="menu-derecho">
            <span class="saludo-usuario">👨‍💼 <?php echo $nombre_admin; ?></span>
            <a href="cerrar_sesion.php" class="btn-cerrar-sesion">Cerrar Sesión</a>
        </div>
    </header>

    <main class="contenedor-rappi">
        <div class="cabecera-populares">
            <div>
                <h1>📋 Mostrador de Cocina y Despacho</h1>
                <p>Gestiona los pedidos de los clientes y asígnalos a un domiciliario.</p>
            </div>
            <button type="button" class="btn-refrescar" onclick="window.location.reload()">🔄 Actualizar pedidos</button>
        </div>

        <?php if (isset($_GET["guardado"])): $mensaje_alerta = "✅ Pedido #" . (int)$_GET["guardado"] . " actualizado correctamente."; ?>
            <div style="background:#e6f9ed; color:#15803d; border:1.5px solid #bbf7d0; padding:12px 16px; border-radius:12px; margin-bottom:20px; font-weight:bold;">
                <?php echo $mensaje_alerta; ?>
            </div>
        <?php endif; ?>

        <div class="lista-mis-pedidos">
            <?php foreach ($pedidos as $ped): ?>
            <article class="tarjeta-pedido">
                <div class="pedido-encabezado">
                    <div>
                        <strong>Pedido #<?php echo (int)$ped['id_pedido']; ?> · <?php echo htmlspecialchars($ped['nombre_cliente']); ?></strong>
                        <small>📅 <?php echo htmlspecialchars($ped['fecha_pedido']); ?></small>
                    </div>
                    <span class="badge-estado estado-<?php echo htmlspecialchars($ped['estado']); ?>">
                        <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($ped['estado']))); ?>
                    </span>
                </div>

                <ul class="pedido-items">
                    <?php foreach ($ped['platos'] as $plato): ?>
                        <li>
                            <span><strong><?php echo (int)$plato['cantidad']; ?>x</strong> <?php echo htmlspecialchars($plato['nombre']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div style="font-size:13px; color:#555; margin: 10px 0;">
                    <div>📍 <strong>Dirección:</strong> <?php echo htmlspecialchars($ped['direccion_entrega']); ?></div>
                    <div>💳 <strong>Pago:</strong> <?php echo ucfirst(htmlspecialchars($ped['metodo_pago'])); ?> · <strong>Total: $<?php echo number_format((float)$ped['total'], 0, ',', '.'); ?></strong></div>
                </div>

                <form method="POST" action="admin.php" style="background:#fafafa; padding:12px; border-radius:12px; border:1px solid #eee; margin-top:10px;">
                    <input type="hidden" name="id_pedido" value="<?php echo (int)$ped['id_pedido']; ?>">

                    <div style="margin-bottom:8px;">
                        <label style="display:block; font-size:12px; font-weight:bold; color:#555; margin-bottom:4px;">Estado del pedido:</label>
                        <select name="estado" style="width:100%; padding:8px; border-radius:8px; border:1.5px solid #ddd;">
                            <option value="pendiente" <?php if($ped['estado']==='pendiente') echo 'selected'; ?>>🕒 Pendiente</option>
                            <option value="preparando" <?php if($ped['estado']==='preparando') echo 'selected'; ?>>👨‍🍳 Preparando en cocina</option>
                            <option value="en_camino" <?php if($ped['estado']==='en_camino') echo 'selected'; ?>>🛵 En camino</option>
                            <option value="entregado" <?php if($ped['estado']==='entregado') echo 'selected'; ?>>✅ Entregado</option>
                            <option value="cancelado" <?php if($ped['estado']==='cancelado') echo 'selected'; ?>>❌ Cancelado</option>
                        </select>
                    </div>

                    <div style="margin-bottom:10px;">
                        <label style="display:block; font-size:12px; font-weight:bold; color:#555; margin-bottom:4px;">Asignar domiciliario:</label>
                        <select name="id_domiciliario" style="width:100%; padding:8px; border-radius:8px; border:1.5px solid #ddd;">
                            <option value="">-- Sin asignar --</option>
                            <?php foreach ($domiciliarios as $dom): ?>
                                <option value="<?php echo (int)$dom['id_usuario']; ?>" <?php if((int)$ped['id_domiciliario'] === (int)$dom['id_usuario']) echo 'selected'; ?>>
                                    🛵 <?php echo htmlspecialchars($dom['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn-principal" style="padding:10px; font-size:14px;">Guardar cambios</button>
                </form>
            </article>
            <?php endforeach; ?>
        </div>
    </main>

</body>
</html>
