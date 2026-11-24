<?php
session_name('ANTOBANO_ADMINISTRADOR'); session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario']) or ($_SESSION['rol'] ?? '') !== 'administrador') {
    header("Location: login.html");
    exit;
}

$nombre_admin = htmlspecialchars($_SESSION['usuario']);
$vista = ($_GET['vista'] ?? 'pedidos') === 'menu' ? 'menu' : 'pedidos';

// 1. Guardar cambios en un pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' and isset($_POST['id_pedido'])) {
    $id_pedido = (int) $_POST['id_pedido'];
    $nuevo_estado = $_POST['estado'] ?? 'pendiente';
    $id_dom = ($_POST['id_domiciliario'] ?? '') !== '' ? (int) $_POST['id_domiciliario'] : null;

    if ($id_dom === null) {
        $stmtUpd = $conexion->prepare("UPDATE pedidos SET estado = ?, id_domiciliario = NULL WHERE id_pedido = ?");
        $stmtUpd->bind_param("si", $nuevo_estado, $id_pedido);
    } else {
        $stmtUpd = $conexion->prepare("UPDATE pedidos SET estado = ?, id_domiciliario = ? WHERE id_pedido = ?");
        $stmtUpd->bind_param("sii", $nuevo_estado, $id_dom, $id_pedido);
    }

    if ($stmtUpd->execute()) {
        header("Location: admin.php?vista=pedidos&guardado=" . $id_pedido);
        exit;
    }
    $stmtUpd->close();
}

// 2. Crear un plato nuevo en el menú
if ($_SERVER['REQUEST_METHOD'] === 'POST' and ($_POST['accion_menu'] ?? '') === 'crear') {
    $id_cat = (int) ($_POST['id_categoria'] ?? 1);
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = (float) ($_POST['precio'] ?? 0);
    $disponible = isset($_POST['disponible']) ? 1 : 0;

    if ($nombre !== '' and $precio > 0) {
        $stmtIns = $conexion->prepare("INSERT INTO productos (id_categoria, nombre, descripcion, precio, disponible) VALUES (?, ?, ?, ?, ?)");
        $stmtIns->bind_param("issdi", $id_cat, $nombre, $descripcion, $precio, $disponible);
        $stmtIns->execute();
        $stmtIns->close();
        header("Location: admin.php?vista=menu&plato_ok=creado");
        exit;
    }
}

// 3. Editar un plato existente
if ($_SERVER['REQUEST_METHOD'] === 'POST' and ($_POST['accion_menu'] ?? '') === 'editar') {
    $id_prod = (int) ($_POST['id_producto'] ?? 0);
    $id_cat = (int) ($_POST['id_categoria'] ?? 1);
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = (float) ($_POST['precio'] ?? 0);
    $disponible = (int) ($_POST['disponible'] ?? 1);

    if ($id_prod > 0 and $nombre !== '' and $precio > 0) {
        $stmtEd = $conexion->prepare("UPDATE productos SET id_categoria = ?, nombre = ?, descripcion = ?, precio = ?, disponible = ? WHERE id_producto = ?");
        $stmtEd->bind_param("issdii", $id_cat, $nombre, $descripcion, $precio, $disponible, $id_prod);
        $stmtEd->execute();
        $stmtEd->close();
        header("Location: admin.php?vista=menu&plato_ok=editado");
        exit;
    }
}

$categoriasNombres = [
    1 => '🍔 Hamburguesas',
    2 => '🌭 Perros Calientes',
    3 => '🍟 Salchipapas',
    4 => '🥤 Bebidas'
];

$domiciliarios = [];
$resDom = $conexion->query("SELECT id_usuario, nombre FROM usuarios WHERE rol = 'domiciliario'");
if ($resDom) {
    while ($d = $resDom->fetch_assoc()) {
        $domiciliarios[] = $d;
    }
}

$pedidos = [];
$resPed = $conexion->query("SELECT p.*, u.nombre AS nombre_cliente FROM pedidos p INNER JOIN usuarios u ON p.id_cliente = u.id_usuario ORDER BY p.id_pedido DESC");
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

$productos = [];
$resProd = $conexion->query("SELECT * FROM productos ORDER BY id_categoria ASC, id_producto ASC");
if ($resProd) {
    while ($pr = $resProd->fetch_assoc()) {
        $productos[] = $pr;
    }
}

$textosEstado = [
    'pendiente'  => '🕒 Pendiente',
    'preparando' => '👨‍🍳 Preparando',
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
    <?php if ($vista === 'pedidos'): ?>
    <meta http-equiv="refresh" content="10">
    <?php endif; ?>
    <title>AntoBano - Administrador</title>
    <link rel="stylesheet" href="style.css?v=12">
</head>
<body>

    <header class="navbar">
        <a href="admin.php?vista=pedidos" class="logo">
            <span class="icono-burger">🍔</span>
            <h2>AntoBano Admin</h2>
        </a>

        <nav class="nav-links">
            <a href="admin.php?vista=pedidos" class="<?php echo $vista === 'pedidos' ? 'activo' : ''; ?>">
                Pedidos (<?php echo count($pedidos); ?>)
            </a>
            <a href="admin.php?vista=menu" class="<?php echo $vista === 'menu' ? 'activo' : ''; ?>">
                Menú de Platos (<?php echo count($productos); ?>)
            </a>
        </nav>

        <div class="menu-derecho">
            <span class="saludo-usuario">👨‍💼 <?php echo $nombre_admin; ?></span>
            <a href="cerrar_sesion.php" class="btn-cerrar-sesion">Cerrar Sesión</a>
        </div>
    </header>

    <main class="contenedor-rappi">

        <?php if (isset($_GET['guardado'])): ?>
            <div style="background:#e6f9ed; color:#15803d; border:1.5px solid #bbf7d0; padding:12px 16px; border-radius:12px; margin-bottom:20px; font-weight:bold;">
                ✅ Pedido #<?php echo (int)$_GET['guardado']; ?> actualizado correctamente.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['plato_ok'])): ?>
            <div style="background:#e6f9ed; color:#15803d; border:1.5px solid #bbf7d0; padding:12px 16px; border-radius:12px; margin-bottom:20px; font-weight:bold;">
                ✅ <?php echo $_GET['plato_ok'] === 'creado' ? '¡Nuevo plato agregado al menú de clientes!' : '¡Cambios del plato guardados correctamente!'; ?>
            </div>
        <?php endif; ?>

        <?php if ($vista === 'pedidos'): ?>
            <div class="cabecera-populares">
                <div>
                    <h1>📋 Mostrador de Cocina y Despacho</h1>
                    <p>Gestiona los pedidos de los clientes y asígnalos a un domiciliario.</p>
                </div>
                <a href="admin.php?vista=pedidos" class="btn-refrescar" style="text-decoration:none;">🔄 Actualizar pedidos</a>
            </div>

            <?php if (empty($pedidos)): ?>
                <div class="sin-pedidos">Aún no hay pedidos registrados en la plataforma.</div>
            <?php else: ?>
                <div class="lista-mis-pedidos">
                    <?php foreach ($pedidos as $ped):
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

                        <ul class="pedido-items">
                            <?php foreach ($ped['platos'] as $plato): ?>
                                <li><span><strong><?php echo (int)$plato['cantidad']; ?>x</strong> <?php echo htmlspecialchars($plato['nombre']); ?></span></li>
                            <?php endforeach; ?>
                        </ul>

                        <div style="font-size:13px; color:#555; margin-bottom:12px;">
                            <div>📍 <strong>Dirección:</strong> <?php echo htmlspecialchars($ped['direccion_entrega']); ?></div>
                            <div>💳 <strong>Pago:</strong> <?php echo ucfirst(htmlspecialchars($ped['metodo_pago'])); ?> · <strong>Total:</strong> $<?php echo number_format((float)$ped['total'], 0, ',', '.'); ?></div>
                        </div>

                        <form method="POST" action="admin.php?vista=pedidos" style="background:#f9f9f9; padding:12px; border-radius:12px; border:1px solid #eee;">
                            <input type="hidden" name="id_pedido" value="<?php echo (int)$ped['id_pedido']; ?>">

                            <label style="display:block; font-size:12px; font-weight:bold; margin-bottom:4px;">Estado del pedido:</label>
                            <select name="estado" style="width:100%; padding:8px; border-radius:8px; border:1px solid #ccc; margin-bottom:10px;">
                                <option value="pendiente" <?php if($est==='pendiente') echo 'selected'; ?>>🕒 Pendiente</option>
                                <option value="preparando" <?php if($est==='preparando') echo 'selected'; ?>>👨‍🍳 Preparando en cocina</option>
                                <option value="en_camino" <?php if($est==='en_camino') echo 'selected'; ?>>🛵 En camino</option>
                                <option value="entregado" <?php if($est==='entregado') echo 'selected'; ?>>✅ Entregado</option>
                                <option value="cancelado" <?php if($est==='cancelado') echo 'selected'; ?>>❌ Cancelado</option>
                            </select>

                            <label style="display:block; font-size:12px; font-weight:bold; margin-bottom:4px;">Asignar domiciliario:</label>
                            <select name="id_domiciliario" style="width:100%; padding:8px; border-radius:8px; border:1px solid #ccc; margin-bottom:12px;">
                                <option value="">-- Sin asignar --</option>
                                <?php foreach ($domiciliarios as $dom): ?>
                                    <option value="<?php echo (int)$dom['id_usuario']; ?>" <?php if((int)$ped['id_domiciliario'] === (int)$dom['id_usuario']) echo 'selected'; ?>>
                                        🛵 <?php echo htmlspecialchars($dom['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button type="submit" class="btn-principal" style="padding:10px; font-size:14px;">Guardar cambios</button>
                        </form>
                    </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="cabecera-populares">
                <div>
                    <h1>🍔 Gestión del Menú de AntoBano</h1>
                    <p>Agrega nuevos platos o modifica precios y disponibilidad al instante.</p>
                </div>
            </div>

            <div style="background:#fff8f1; border:1.5px solid #ffd8b5; border-radius:16px; padding:18px; margin-bottom:28px;">
                <h3 style="margin-top:0; color:#FF7802;">➕ Agregar Nuevo Plato</h3>
                <form method="POST" action="admin.php?vista=menu" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px; align-items:end;">
                    <input type="hidden" name="accion_menu" value="crear">

                    <div>
                        <label style="display:block; font-size:12px; font-weight:bold; margin-bottom:4px;">Categoría:</label>
                        <select name="id_categoria" style="width:100%; padding:9px; border-radius:8px; border:1px solid #ccc;">
                            <?php foreach ($categoriasNombres as $idC => $nomC): ?>
                                <option value="<?php echo $idC; ?>"><?php echo $nomC; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label style="display:block; font-size:12px; font-weight:bold; margin-bottom:4px;">Nombre del plato:</label>
                        <input type="text" name="nombre" required placeholder="Ej: Hamburguesa BBQ" style="width:100%; padding:9px; border-radius:8px; border:1px solid #ccc; box-sizing:border-box;">
                    </div>

                    <div>
                        <label style="display:block; font-size:12px; font-weight:bold; margin-bottom:4px;">Precio ($ COP):</label>
                        <input type="number" name="precio" required min="500" step="100" placeholder="Ej: 18000" style="width:100%; padding:9px; border-radius:8px; border:1px solid #ccc; box-sizing:border-box;">
                    </div>

                    <div style="grid-column: 1 / -1;">
                        <label style="display:block; font-size:12px; font-weight:bold; margin-bottom:4px;">Descripción / Ingredientes:</label>
                        <input type="text" name="descripcion" placeholder="Ej: Carne artesanal 150g, salsa BBQ, aros de cebolla y queso..." style="width:100%; padding:9px; border-radius:8px; border:1px solid #ccc; box-sizing:border-box;">
                    </div>

                    <div style="display:flex; align-items:center; gap:8px;">
                        <input type="checkbox" id="disp_nuevo" name="disponible" checked>
                        <label for="disp_nuevo" style="font-size:13px; font-weight:bold;">Disponible en catálogo</label>
                    </div>

                    <div>
                        <button type="submit" class="btn-principal" style="padding:10px; font-size:14px;">Crear Plato</button>
                    </div>
                </form>
            </div>

            <div class="lista-mis-pedidos">
                <?php foreach ($productos as $prod): ?>
                <article class="tarjeta-pedido">
                    <form method="POST" action="admin.php?vista=menu">
                        <input type="hidden" name="accion_menu" value="editar">
                        <input type="hidden" name="id_producto" value="<?php echo (int)$prod['id_producto']; ?>">

                        <div class="pedido-encabezado">
                            <strong>Plato #<?php echo (int)$prod['id_producto']; ?></strong>
                            <span class="badge-estado <?php echo (int)$prod['disponible'] === 1 ? 'estado-entregado' : 'estado-cancelado'; ?>">
                                <?php echo (int)$prod['disponible'] === 1 ? '✅ Disponible' : '⛔ Agotado'; ?>
                            </span>
                        </div>

                        <label style="display:block; font-size:12px; font-weight:bold; margin-top:8px; margin-bottom:4px;">Nombre:</label>
                        <input type="text" name="nombre" required value="<?php echo htmlspecialchars($prod['nombre']); ?>" style="width:100%; padding:8px; border-radius:8px; border:1px solid #ccc; box-sizing:border-box; margin-bottom:8px;">

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:8px; margin-bottom:8px;">
                            <div>
                                <label style="display:block; font-size:12px; font-weight:bold; margin-bottom:4px;">Categoría:</label>
                                <select name="id_categoria" style="width:100%; padding:8px; border-radius:8px; border:1px solid #ccc;">
                                    <?php foreach ($categoriasNombres as $idC => $nomC): ?>
                                        <option value="<?php echo $idC; ?>" <?php if((int)$prod['id_categoria'] === $idC) echo 'selected'; ?>><?php echo $nomC; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label style="display:block; font-size:12px; font-weight:bold; margin-bottom:4px;">Precio ($):</label>
                                <input type="number" name="precio" required min="500" step="100" value="<?php echo (int)$prod['precio']; ?>" style="width:100%; padding:8px; border-radius:8px; border:1px solid #ccc; box-sizing:border-box;">
                            </div>
                        </div>

                        <label style="display:block; font-size:12px; font-weight:bold; margin-bottom:4px;">Descripción:</label>
                        <textarea name="descripcion" rows="2" style="width:100%; padding:8px; border-radius:8px; border:1px solid #ccc; box-sizing:border-box; margin-bottom:8px;"><?php echo htmlspecialchars($prod['descripcion'] ?? ''); ?></textarea>

                        <label style="display:block; font-size:12px; font-weight:bold; margin-bottom:4px;">Estado en menú:</label>
                        <select name="disponible" style="width:100%; padding:8px; border-radius:8px; border:1px solid #ccc; margin-bottom:12px;">
                            <option value="1" <?php if((int)$prod['disponible'] === 1) echo 'selected'; ?>>✅ Disponible para clientes</option>
                            <option value="0" <?php if((int)$prod['disponible'] === 0) echo 'selected'; ?>>⛔ Agotado (ocultar)</option>
                        </select>

                        <button type="submit" class="btn-principal" style="padding:10px; font-size:14px;">Guardar Plato</button>
                    </form>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

</body>
</html>
