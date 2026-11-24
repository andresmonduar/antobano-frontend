<?php
session_name('ANTOBANO_ADMINISTRADOR'); session_start();
require 'conexion.php';

// Sube la imagen de un plato (JPG, PNG o WEBP, máx. 2 MB). Devuelve el nombre del archivo o null.
function subirImagenPlato(string $campo): ?string {
    if (!isset($_FILES[$campo]) || $_FILES[$campo]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    if ($_FILES[$campo]['size'] > 2 * 1024 * 1024) {
        return null;
    }
    $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES[$campo]['tmp_name']);
    if (!isset($permitidos[$mime])) {
        return null;
    }
    $nombre = 'plato_' . bin2hex(random_bytes(8)) . '.' . $permitidos[$mime];
    $destino = __DIR__ . '/imagenes/productos/' . $nombre;
    return move_uploaded_file($_FILES[$campo]['tmp_name'], $destino) ? $nombre : null;
}

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
        $imagen = subirImagenPlato('imagen');
        $stmtIns = $conexion->prepare("INSERT INTO productos (id_categoria, nombre, descripcion, imagen, precio, disponible) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtIns->bind_param("isssdi", $id_cat, $nombre, $descripcion, $imagen, $precio, $disponible);
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
        $imagen = subirImagenPlato('imagen');
        if ($imagen !== null) {
            $stmtEd = $conexion->prepare("UPDATE productos SET id_categoria = ?, nombre = ?, descripcion = ?, imagen = ?, precio = ?, disponible = ? WHERE id_producto = ?");
            $stmtEd->bind_param("isssdii", $id_cat, $nombre, $descripcion, $imagen, $precio, $disponible, $id_prod);
        } else {
            $stmtEd = $conexion->prepare("UPDATE productos SET id_categoria = ?, nombre = ?, descripcion = ?, precio = ?, disponible = ? WHERE id_producto = ?");
            $stmtEd->bind_param("issdii", $id_cat, $nombre, $descripcion, $precio, $disponible, $id_prod);
        }
        $stmtEd->execute();
        $stmtEd->close();
        header("Location: admin.php?vista=menu&plato_ok=editado");
        exit;
    }
}

// 4. Crear o editar una categoría
if ($_SERVER['REQUEST_METHOD'] === 'POST' and in_array(($_POST['accion_cat'] ?? ''), ['crear_cat', 'editar_cat'], true)) {
    $accion_cat = $_POST['accion_cat'];
    $nombre_cat = trim($_POST['nombre_cat'] ?? '');
    $desc_cat   = trim($_POST['descripcion_cat'] ?? '');
    $id_cat_ed  = (int) ($_POST['id_categoria'] ?? 0);

    if ($nombre_cat !== '' and ($accion_cat === 'crear_cat' or $id_cat_ed > 0)) {
        $resultado = 'error';
        try {
            if ($accion_cat === 'crear_cat') {
                $stmtCat = $conexion->prepare("INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)");
                $stmtCat->bind_param("ss", $nombre_cat, $desc_cat);
                $okMsg = 'creada';
            } else {
                $stmtCat = $conexion->prepare("UPDATE categorias SET nombre = ?, descripcion = ? WHERE id_categoria = ?");
                $stmtCat->bind_param("ssi", $nombre_cat, $desc_cat, $id_cat_ed);
                $okMsg = 'editada';
            }

            if ($stmtCat->execute()) {
                $resultado = 'ok';
            } elseif ($conexion->errno === 1062) {
                $resultado = 'duplicada';
            }
            $stmtCat->close();
        } catch (mysqli_sql_exception $e) {
            // Con PHP 8.1+ los errores de MySQL llegan como excepción
            $resultado = ((int) $e->getCode() === 1062) ? 'duplicada' : 'error';
        }

        if ($resultado === 'ok') {
            header("Location: admin.php?vista=menu&cat_ok=" . $okMsg);
        } else {
            header("Location: admin.php?vista=menu&cat_err=" . $resultado);
        }
        exit;
    }
}

// Categorías desde la base de datos (reemplaza la lista fija anterior)
$categorias = [];
$resCat = $conexion->query("SELECT id_categoria, nombre, descripcion FROM categorias ORDER BY id_categoria ASC");
if ($resCat) {
    while ($c = $resCat->fetch_assoc()) {
        $categorias[] = $c;
    }
}

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

        <?php if (isset($_GET['cat_ok'])): ?>
            <div style="background:#e6f9ed; color:#15803d; border:1.5px solid #bbf7d0; padding:12px 16px; border-radius:12px; margin-bottom:20px; font-weight:bold;">
                ✅ <?php echo $_GET['cat_ok'] === 'creada' ? '¡Nueva categoría creada correctamente!' : '¡Categoría actualizada correctamente!'; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['cat_err'])): ?>
            <div style="background:#fef2f2; color:#b91c1c; border:1.5px solid #fecaca; padding:12px 16px; border-radius:12px; margin-bottom:20px; font-weight:bold;">
                ⚠️ <?php echo $_GET['cat_err'] === 'duplicada' ? 'No se pudo guardar: ya existe una categoría con ese nombre.' : 'No se pudo guardar la categoría. Inténtalo de nuevo.'; ?>
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
                    <p>Crea categorías, agrega platos o modifica precios y disponibilidad al instante.</p>
                </div>
            </div>

            <!-- SECCIÓN 1: CATEGORÍAS (crear y editar) -->
            <div style="background:#fff8f1; border:1.5px solid #ffd8b5; border-radius:16px; padding:18px; margin-bottom:24px;">
                <h3 style="margin-top:0; color:#FF7802;">🏷️ Categorías (Crear o Editar)</h3>

                <form method="POST" action="admin.php?vista=menu" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px; align-items:end; margin-bottom:18px; padding-bottom:16px; border-bottom:1px solid #ffe4cc;">
                    <input type="hidden" name="accion_cat" value="crear_cat">
                    <div>
                        <label style="display:block; font-size:12px; font-weight:bold; margin-bottom:4px;">Nueva categoría:</label>
                        <input type="text" name="nombre_cat" required maxlength="60" placeholder="Ej: Combos o Postres" style="width:100%; padding:9px; border-radius:8px; border:1px solid #ccc; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block; font-size:12px; font-weight:bold; margin-bottom:4px;">Descripción:</label>
                        <input type="text" name="descripcion_cat" maxlength="200" placeholder="Ej: Combos especiales con bebida" style="width:100%; padding:9px; border-radius:8px; border:1px solid #ccc; box-sizing:border-box;">
                    </div>
                    <div>
                        <button type="submit" class="btn-principal" style="padding:10px; font-size:14px;">+ Agregar Categoría</button>
                    </div>
                </form>

                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:12px;">
                    <?php foreach ($categorias as $cat): ?>
                        <form method="POST" action="admin.php?vista=menu" style="background:#ffffff; border:1px solid #eee; border-radius:12px; padding:12px;">
                            <input type="hidden" name="accion_cat" value="editar_cat">
                            <input type="hidden" name="id_categoria" value="<?php echo (int)$cat['id_categoria']; ?>">
                            <div style="font-size:12px; color:#777; margin-bottom:6px;">ID #<?php echo (int)$cat['id_categoria']; ?></div>

                            <label style="display:block; font-size:12px; font-weight:bold; margin-bottom:3px;">Nombre:</label>
                            <input type="text" name="nombre_cat" required maxlength="60" value="<?php echo htmlspecialchars($cat['nombre']); ?>" style="width:100%; padding:7px; border-radius:8px; border:1px solid #ccc; box-sizing:border-box; margin-bottom:8px;">

                            <label style="display:block; font-size:12px; font-weight:bold; margin-bottom:3px;">Descripción:</label>
                            <input type="text" name="descripcion_cat" maxlength="200" value="<?php echo htmlspecialchars($cat['descripcion'] ?? ''); ?>" style="width:100%; padding:7px; border-radius:8px; border:1px solid #ccc; box-sizing:border-box; margin-bottom:10px;">

                            <button type="submit" class="btn-principal" style="padding:8px; font-size:13px;">Guardar categoría</button>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- SECCIÓN 2: AGREGAR NUEVO PLATO -->
            <div style="background:#fff8f1; border:1.5px solid #ffd8b5; border-radius:16px; padding:18px; margin-bottom:28px;">
                <h3 style="margin-top:0; color:#FF7802;">➕ Agregar Nuevo Plato</h3>
                <form method="POST" enctype="multipart/form-data" action="admin.php?vista=menu" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px; align-items:end;">
                    <input type="hidden" name="accion_menu" value="crear">

                    <div>
                        <label style="display:block; font-size:12px; font-weight:bold; margin-bottom:4px;">Categoría:</label>
                        <select name="id_categoria" style="width:100%; padding:9px; border-radius:8px; border:1px solid #ccc;">
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo (int)$cat['id_categoria']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
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

                    <div style="grid-column: 1 / -1;">
                        <label style="display:block; font-size:12px; font-weight:bold; margin-bottom:4px;">Imagen del plato (JPG, PNG o WEBP, máx. 2 MB):</label>
                        <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" style="width:100%; padding:8px; border-radius:8px; border:1px solid #ccc; box-sizing:border-box;">
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

            <!-- SECCIÓN 3: EDITAR PLATOS EXISTENTES -->
            <div class="lista-mis-pedidos">
                <?php foreach ($productos as $prod): ?>
                <article class="tarjeta-pedido">
                    <form method="POST" enctype="multipart/form-data" action="admin.php?vista=menu">
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
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?php echo (int)$cat['id_categoria']; ?>" <?php if((int)$prod['id_categoria'] === (int)$cat['id_categoria']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($cat['nombre']); ?>
                                        </option>
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

                        <label style="display:block; font-size:12px; font-weight:bold; margin-bottom:4px;">Imagen:</label>
                        <?php if (!empty($prod['imagen'])): ?>
                            <img src="imagenes/productos/<?php echo htmlspecialchars($prod['imagen']); ?>" alt="" style="width:100%; max-height:140px; object-fit:cover; border-radius:8px; margin-bottom:6px;">
                        <?php endif; ?>
                        <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" style="width:100%; margin-bottom:8px;">

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
