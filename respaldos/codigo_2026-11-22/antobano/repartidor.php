<?php
session_name('ANTOBANO_DOMICILIARIO'); session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario']) or ($_SESSION['rol'] ?? '') !== 'domiciliario') {
    header("Location: login.html");
    exit;
}

$nombre_repartidor = htmlspecialchars($_SESSION['usuario']);
$id_domiciliario = (int) $_SESSION['id_usuario'];
$mensaje_alerta = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' and isset($_POST['id_pedido'], $_POST['nuevo_estado'])) {
    $id_pedido = (int) $_POST['id_pedido'];
    $nuevo_estado = $_POST['nuevo_estado'];

    if ($nuevo_estado === 'en_camino' or $nuevo_estado === 'entregado') {
        $stmtUpd = $conexion->prepare("UPDATE pedidos SET estado = ? WHERE id_pedido = ? AND id_domiciliario = ?");
        $stmtUpd->bind_param("sii", $nuevo_estado, $id_pedido, $id_domiciliario);
        if ($stmtUpd->execute()) {
            $texto = ($nuevo_estado === 'en_camino') ? '🛵 ¡En camino hacia el cliente!' : '✅ ¡Pedido marcado como entregado!';
            header("Location: repartidor.php?ok=1"); exit;
        }
        $stmtUpd->close();
    }
}

$entregas = [];
$stmtEnt = $conexion->prepare("SELECT p.*, u.nombre AS nombre_cliente 
                               FROM pedidos p 
                               INNER JOIN usuarios u ON p.id_cliente = u.id_usuario 
                               WHERE p.id_domiciliario = ? 
                               ORDER BY p.id_pedido DESC");
$stmtEnt->bind_param("i", $id_domiciliario);
$stmtEnt->execute();
$resEnt = $stmtEnt->get_result();

while ($ped = $resEnt->fetch_assoc()) {
    $id_p = (int) $ped['id_pedido'];
    $ped['platos'] = [];
    $resDet = $conexion->query("SELECT d.cantidad, pr.nombre FROM detalle_pedido d INNER JOIN productos pr ON d.id_producto = pr.id_producto WHERE d.id_pedido = $id_p");
    while ($det = $resDet->fetch_assoc()) {
        $ped['platos'][] = $det;
    }
    $entregas[] = $ped;
}
$stmtEnt->close();

$textosEstado = [
    'pendiente'  => '🕒 Pendiente',
    'preparando' => '👨‍🍳 Preparando en cocina',
    'en_camino'  => '🛵 En camino',
    'entregado'  => '✅ Entregado',
    'cancelado'  => '❌ Cancelado'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AntoBano - Domiciliario</title>
    <link rel="stylesheet" href="style.css?v=12">
</head>
<body>

    <header class="navbar">
        <a href="repartidor.php" class="logo">
            <span class="icono-burger">🛵</span>
            <h2>AntoBano Reparto</h2>
        </a>

        <nav class="nav-links">
            <a href="repartidor.php" class="activo">Mis Entregas (<?php echo count($entregas); ?>)</a>
        </nav>

        <div class="menu-derecho">
            <span class="saludo-usuario">🛵 <?php echo $nombre_repartidor; ?></span>
            <a href="cerrar_sesion.php" class="btn-cerrar-sesion">Cerrar Sesión</a>
        </div>
    </header>

    <main class="contenedor-rappi">
        <div class="cabecera-populares">
            <div>
                <h1>📦 Mis Domicilios Asignados</h1>
                <p>Revisa la dirección de entrega y actualiza el estado en ruta.</p>
            </div>
            <button type="button" class="btn-refrescar" onclick="window.location.reload()">🔄 Actualizar lista</button>
        </div>

        <?php if (isset($_GET["ok"])): $mensaje_alerta = "✅ Estado del pedido actualizado."; ?>
            <div style="background:#e6f9ed; color:#15803d; border:1.5px solid #bbf7d0; padding:12px 16px; border-radius:12px; margin-bottom:20px; font-weight:bold;">
                <?php echo $mensaje_alerta; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($entregas)): ?>
            <div class="sin-pedidos">
                No tienes domicilios asignados en este momento. Cuando el restaurante te asigne uno, aparecerá aquí.
            </div>
        <?php else: ?>
            <div class="lista-mis-pedidos">
                <?php foreach ($entregas as $ped): 
                    $est = $ped['estado'];
                    $etiqueta = $textosEstado[$est] ?? $est;
                ?>
                <article class="tarjeta-pedido">
                    <div class="pedido-encabezado">
                        <div>
                            <strong>Pedido #<?php echo (int)$ped['id_pedido']; ?> · <?php echo htmlspecialchars($ped['nombre_cliente']); ?></strong>
                            <small>📅 <?php echo htmlspecialchars($ped['fecha_pedido']); ?></small>
                        </div>
                        <span class="badge-estado estado-<?php echo htmlspecialchars($est); ?>"><?php echo $etiqueta; ?></span>
                    </div>

                    <div style="background:#fff8f1; border:1px solid #ffe0c2; border-radius:10px; padding:10px 12px; margin:12px 0; font-size:13.5px;">
                        <div>📍 <strong>Entregar en:</strong> <?php echo htmlspecialchars($ped['direccion_entrega']); ?></div>
                        <div style="margin-top:4px;">💵 <strong>Cobrar (<?php echo ucfirst(htmlspecialchars($ped['metodo_pago'])); ?>):</strong> $<?php echo number_format((float)$ped['total'], 0, ',', '.'); ?></div>
                    </div>

                    <ul class="pedido-items">
                        <?php foreach ($ped['platos'] as $plato): ?>
                            <li>
                                <span><strong><?php echo (int)$plato['cantidad']; ?>x</strong> <?php echo htmlspecialchars($plato['nombre']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if ($est !== 'entregado' and $est !== 'cancelado'): ?>
                    <form method="POST" action="repartidor.php" style="display:flex; gap:10px; margin-top:14px;">
                        <input type="hidden" name="id_pedido" value="<?php echo (int)$ped['id_pedido']; ?>">
                        
                        <?php if ($est !== 'en_camino'): ?>
                            <button type="submit" name="nuevo_estado" value="en_camino" class="btn-principal" style="background-color:#7e22ce; padding:10px; font-size:14px;">
                                🛵 Salí con el pedido
                            </button>
                        <?php endif; ?>

                        <button type="submit" name="nuevo_estado" value="entregado" class="btn-principal" style="background-color:#15803d; padding:10px; font-size:14px;">
                            ✅ Pedido entregado
                        </button>
                    </form>
                    <?php endif; ?>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

</body>
</html>
