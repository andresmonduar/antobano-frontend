<?php
session_name('ANTOBANO_CLIENTE'); session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario']) or !isset($_SESSION['id_usuario'])) {
    header("Location: login.html");
    exit;
}

$nombre_usuario = htmlspecialchars($_SESSION['usuario']);
$id_cliente = (int) $_SESSION['id_usuario'];

$productos = [];
$consultaProd = $conexion->query("SELECT id_producto, id_categoria, nombre, descripcion, imagen, precio FROM productos WHERE disponible = 1");
if ($consultaProd) {
    while ($fila = $consultaProd->fetch_assoc()) {
        $productos[] = $fila;
    }
}

// Categorías desde la base de datos (las que crea el administrador)
$categorias = [];
$nombreCat = [];
$consultaCat = $conexion->query("SELECT id_categoria, nombre, descripcion FROM categorias ORDER BY id_categoria ASC");
if ($consultaCat) {
    while ($c = $consultaCat->fetch_assoc()) {
        $categorias[] = $c;
        $nombreCat[(int)$c['id_categoria']] = $c['nombre'];
    }
}

$emojisCategoria = [1 => '🍔', 2 => '🌭', 3 => '🍟', 4 => '🥤'];
$fotoPorDefecto = 'https://images.unsplash.com/photo-1550547660-d9450f859349?auto=format&fit=crop&w=600&q=80';

$mis_pedidos = [];
$stmtPedidos = $conexion->prepare("SELECT id_pedido, direccion_entrega, metodo_pago, estado, total, fecha_pedido FROM pedidos WHERE id_cliente = ? ORDER BY id_pedido DESC");
$stmtPedidos->bind_param("i", $id_cliente);
$stmtPedidos->execute();
$resPedidos = $stmtPedidos->get_result();

while ($pedido = $resPedidos->fetch_assoc()) {
    $id_ped = (int) $pedido['id_pedido'];
    $pedido['platos'] = [];
    $stmtDet = $conexion->prepare("SELECT d.cantidad, d.subtotal, p.nombre FROM detalle_pedido d INNER JOIN productos p ON d.id_producto = p.id_producto WHERE d.id_pedido = ?");
    $stmtDet->bind_param("i", $id_ped);
    $stmtDet->execute();
    $resDet = $stmtDet->get_result();
    while ($det = $resDet->fetch_assoc()) {
        $pedido['platos'][] = $det;
    }
    $stmtDet->close();
    $mis_pedidos[] = $pedido;
}
$stmtPedidos->close();

$imagenesCategoria = [
    1 => 'https://images.unsplash.com/photo-1550547660-d9450f859349?auto=format&fit=crop&w=600&q=80',
    2 => 'https://images.unsplash.com/photo-1619740455993-9e612b1af08a?auto=format&fit=crop&w=600&q=80',
    3 => 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?auto=format&fit=crop&w=600&q=80',
    4 => 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?auto=format&fit=crop&w=600&q=80'
];

$nombresLocal = [
    1 => 'Burger House',
    2 => 'Perros del Parque',
    3 => 'Papas & Co.',
    4 => 'Refrescos AntoBano'
];

$restaurantesAliados = [
    [
        'id_categoria' => '1',
        'nombre' => 'Burger House',
        'especialidad' => 'Hamburguesas artesanales a la parrilla con pan brioche.',
        'tiempo' => '25-35 min',
        'calificacion' => '4.9',
        'imagen' => 'https://images.unsplash.com/photo-1550547660-d9450f859349?auto=format&fit=crop&w=600&q=80'
    ],
    [
        'id_categoria' => '2',
        'nombre' => 'Perros del Parque',
        'especialidad' => 'Perros calientes estilo callejero con queso fundido y salsas.',
        'tiempo' => '20-30 min',
        'calificacion' => '4.8',
        'imagen' => 'https://images.unsplash.com/photo-1619740455993-9e612b1af08a?auto=format&fit=crop&w=600&q=80'
    ],
    [
        'id_categoria' => '3',
        'nombre' => 'Papas & Co.',
        'especialidad' => 'Salchipapas cargadas, papa francesa crocante y carnes mixtas.',
        'tiempo' => '25-35 min',
        'calificacion' => '4.7',
        'imagen' => 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?auto=format&fit=crop&w=600&q=80'
    ],
    [
        'id_categoria' => '4',
        'nombre' => 'Refrescos AntoBano',
        'especialidad' => 'Gaseosas frías, limonadas naturales y bebidas para acompañar.',
        'tiempo' => '15-20 min',
        'calificacion' => '4.9',
        'imagen' => 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?auto=format&fit=crop&w=600&q=80'
    ]
];

// Categorías nuevas (creadas en admin) también aparecen como restaurantes aliados
$idsConRestaurante = array_map(function ($r) { return (int)$r['id_categoria']; }, $restaurantesAliados);
foreach ($categorias as $c) {
    if (!in_array((int)$c['id_categoria'], $idsConRestaurante, true)) {
        $restaurantesAliados[] = [
            'id_categoria' => (string)(int)$c['id_categoria'],
            'nombre' => $c['nombre'],
            'especialidad' => ($c['descripcion'] ?? '') !== '' ? $c['descripcion'] : 'Los mejores platos de ' . $c['nombre'] . '.',
            'tiempo' => '20-35 min',
            'calificacion' => '4.8',
            'imagen' => $fotoPorDefecto
        ];
    }
}

$textosEstado = [
    'pendiente'  => '🕒 Pendiente por confirmar',
    'preparando' => '👨‍🍳 En cocina preparándose',
    'en_camino'  => '🛵 Domiciliario en camino',
    'entregado'  => '✅ Entregado',
    'cancelado'  => '❌ Cancelado'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php if (($_GET["vista"] ?? "") === "pedidos") echo "<meta http-equiv=\"refresh\" content=\"10\">"; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AntoBano - ¿Qué se te antoja hoy?</title>
    <link rel="stylesheet" href="style.css?v=8">
</head>
<body>

    <!-- Barra superior fija: no cambia al navegar entre apartados -->
    <header class="navbar">
        <a href="javascript:void(0)" onclick="cambiarVista('inicio')" class="logo">
            <span class="icono-burger">🍔</span>
            <h2>AntoBano</h2>
        </a>

        <nav class="nav-links" id="menu-navegacion">
            <a href="javascript:void(0)" id="nav-inicio" class="activo" onclick="cambiarVista('inicio')">Inicio</a>
            <a href="javascript:void(0)" id="nav-restaurantes" onclick="cambiarVista('restaurantes')">Restaurantes</a>
            <a href="#promo" id="nav-ofertas" onclick="cambiarVista('inicio')">Ofertas</a>
            <a href="javascript:void(0)" id="nav-pedidos" onclick="cambiarVista('pedidos')">Pedidos (<?php echo count($mis_pedidos); ?>)</a>
        </nav>

        <div class="menu-derecho">
            <span class="saludo-usuario">Hola, <?php echo $nombre_usuario; ?></span>
            <button type="button" class="btn-carrito-nav" onclick="abrirCarrito()">
                🛒 <span id="contador-carrito">0</span>
            </button>
            <button type="button" class="btn-hamburgesa" onclick="alternarMenuMovil()">☰</button>
            <a href="cerrar_sesion.php" class="btn-cerrar-sesion">Cerrar Sesión</a>
        </div>
    </header>

    <main class="contenedor-rappi">

        <!-- APARTADO 1: INICIO / MENÚ PRINCIPAL -->
        <div id="vista-inicio">
            <section class="cabecera-antojo">
                <div>
                    <h1>Hola, ¿qué se te antoja?</h1>
                    <p>Algo rico está a unos minutos de tu puerta.</p>
                </div>
                <div class="caja-direccion" onclick="cambiarDireccion()">
                    <span class="icono-pin">📍</span>
                    <div>
                        <small>ENTREGAR EN</small>
                        <strong id="texto-direccion">Av. Libertadores # 15-40, Cúcuta ⌄</strong>
                    </div>
                </div>
            </section>

            <section id="promo" class="banner-promocion">
                <div class="promo-texto">
                    <span class="promo-etiqueta">RECIÉN HECHO, SOLO PARA TI</span>
                    <h2>Promoción del Día</h2>
                    <p>Hamburguesa Doble Tocineta recién salida de la parrilla.<br>El mejor plan es darte gusto.</p>
                    <button type="button" class="btn-pedir-ahora" onclick="agregarAlCarrito(2, 'Hamburguesa Doble Tocineta', 23000); abrirCarrito();">
                        ➔ Pedir ahora
                    </button>
                </div>
                <div class="promo-imagen">
                    <img src="https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=800&q=80" alt="Hamburguesa">
                </div>
            </section>

            <section class="barra-categorias">
                <?php foreach ($categorias as $c):
                    $idC = (int)$c['id_categoria'];
                    $emoji = $emojisCategoria[$idC] ?? '🍽️';
                    $sub = ($c['descripcion'] ?? '') !== '' ? $c['descripcion'] : 'Ver platos';
                ?>
                <button type="button" id="cat-btn-<?php echo $idC; ?>" class="tarjeta-cat" onclick="filtrarCategoria('<?php echo $idC; ?>', this)">
                    <span class="cat-titulo"><?php echo $emoji . ' ' . htmlspecialchars($c['nombre']); ?></span>
                    <span class="cat-sub"><?php echo htmlspecialchars($sub); ?></span>
                </button>
                <?php endforeach; ?>
            </section>

            <section id="populares" class="seccion-populares">
                <div class="cabecera-populares">
                    <div>
                        <h2>Categorías Populares</h2>
                        <p>Los favoritos que siempre se antojan.</p>
                    </div>
                    <div class="tiempo-entrega">🕒 En tu puerta en 25-35 min</div>
                </div>

                <div class="grid-rappi" id="grid-productos">
                    <?php foreach ($productos as $prod): 
                        $cat = (int)$prod['id_categoria'];
                        $fotoCat = $imagenesCategoria[$cat] ?? $fotoPorDefecto;
                        $archivoImg = !empty($prod['imagen']) ? basename($prod['imagen']) : '';
                        if ($archivoImg !== '' && is_file(__DIR__ . '/imagenes/productos/' . $archivoImg)) {
                            $foto = 'imagenes/productos/' . rawurlencode($archivoImg);
                        } else {
                            $foto = $fotoCat;
                        }
                        $local = $nombresLocal[$cat] ?? ($nombreCat[$cat] ?? 'AntoBano Kitchen');
                        $precioNum = (float)$prod['precio'];
                    ?>
                    <article class="card-rappi" data-categoria="<?php echo $cat; ?>">
                        <div class="card-foto">
                            <img src="<?php echo $foto; ?>" alt="<?php echo htmlspecialchars($prod['nombre']); ?>">
                        </div>
                        <div class="card-cuerpo">
                            <span class="card-local"><?php echo $local; ?></span>
                            <h3><?php echo htmlspecialchars($prod['nombre']); ?></h3>
                            <p><?php echo htmlspecialchars($prod['descripcion']); ?></p>
                            <div class="card-pie">
                                <span class="card-precio">$<?php echo number_format($precioNum, 0, ',', '.'); ?></span>
                                <button type="button" class="btn-sumar" onclick="agregarAlCarrito(<?php echo (int)$prod['id_producto']; ?>, '<?php echo addslashes($prod['nombre']); ?>', <?php echo $precioNum; ?>)">+ Agregar</button>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>

        <!-- APARTADO 2: RESTAURANTES ALIADOS -->
        <div id="vista-restaurantes" style="display: none;">
            <div class="cabecera-populares">
                <div>
                    <h2>🏪 Restaurantes Aliados</h2>
                    <p>Elige tu local favorito y explora sus platos disponibles.</p>
                </div>
                <button type="button" class="btn-sumar" onclick="cambiarVista('inicio')">← Volver al inicio</button>
            </div>

            <div class="grid-rappi">
                <?php foreach ($restaurantesAliados as $rest): ?>
                <article class="card-rappi">
                    <div class="card-foto">
                        <img src="<?php echo $rest['imagen']; ?>" alt="<?php echo htmlspecialchars($rest['nombre']); ?>">
                    </div>
                    <div class="card-cuerpo">
                        <span class="card-local">⭐ <?php echo $rest['calificacion']; ?> · 🕒 <?php echo $rest['tiempo']; ?></span>
                        <h3><?php echo htmlspecialchars($rest['nombre']); ?></h3>
                        <p><?php echo htmlspecialchars($rest['especialidad']); ?></p>
                        <div class="card-pie">
                            <span style="font-size: 12.5px; color: #15803d; font-weight: bold;">● Abierto ahora</span>
                            <button type="button" class="btn-sumar" onclick="abrirMenuRestaurante('<?php echo $rest['id_categoria']; ?>')">Ver menú ➔</button>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- APARTADO 3: MIS PEDIDOS -->
        <div id="vista-pedidos" style="display: none;">
            <div class="cabecera-populares">
                <div>
                    <h2>📦 Mis Pedidos</h2>
                    <p>Revisa el estado en tiempo real de tus órdenes.</p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn-refrescar" onclick="window.location.href='inicio.php#mis-pedidos'; window.location.reload();">🔄 Actualizar estado</button>
                    <button type="button" class="btn-sumar" onclick="cambiarVista('inicio')">← Volver al menú</button>
                </div>
            </div>

            <?php if (empty($mis_pedidos)): ?>
                <div class="sin-pedidos">Aún no has realizado ningún pedido. ¡Elige tu favorito en el menú y pídelo ahora!</div>
            <?php else: ?>
                <div class="lista-mis-pedidos">
                    <?php foreach ($mis_pedidos as $ped): 
                        $est = $ped['estado'];
                        $etiqueta = $textosEstado[$est] ?? $est;
                    ?>
                    <article class="tarjeta-pedido">
                        <div class="pedido-encabezado">
                            <div>
                                <strong>Pedido #<?php echo (int)$ped['id_pedido']; ?></strong>
                                <small>📅 <?php echo htmlspecialchars($ped['fecha_pedido']); ?></small>
                            </div>
                            <span class="badge-estado estado-<?php echo htmlspecialchars($est); ?>"><?php echo $etiqueta; ?></span>
                        </div>
                        <ul class="pedido-items">
                            <?php foreach ($ped['platos'] as $plato): ?>
                                <li>
                                    <span><strong><?php echo (int)$plato['cantidad']; ?>x</strong> <?php echo htmlspecialchars($plato['nombre']); ?></span>
                                    <span>$<?php echo number_format((float)$plato['subtotal'], 0, ',', '.'); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="pedido-pie">
                            <span>📍 <?php echo htmlspecialchars($ped['direccion_entrega']); ?> · 💳 <?php echo ucfirst(htmlspecialchars($ped['metodo_pago'])); ?></span>
                            <strong class="pedido-total">Total: $<?php echo number_format((float)$ped['total'], 0, ',', '.'); ?></strong>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </main>

    <!-- Panel lateral del Carrito -->
    <div id="fondo-carrito" class="fondo-carrito" onclick="cerrarCarrito()" style="display: none;"></div>
    <aside id="panel-carrito" class="panel-carrito">
        <div class="cabecera-carrito">
            <h2>🛒 Tu Pedido</h2>
            <button type="button" class="btn-cerrar-carrito" onclick="cerrarCarrito()">✕</button>
        </div>
        <div id="lista-carrito" class="lista-carrito">
            <p class="carrito-vacio">Aún no has agregado comida a tu carrito.</p>
        </div>
        <div class="pie-carrito">
            <div style="margin-bottom: 12px;">
                <label for="metodo-pago" style="display:block; font-size:12px; font-weight:bold; color:#555; margin-bottom:5px;">Método de pago:</label>
                <select id="metodo-pago" style="width:100%; padding:10px; border-radius:10px; border:1.5px solid #ddd; font-size:14px;">
                    <option value="efectivo">💵 Efectivo contra entrega</option>
                    <option value="nequi">📱 Nequi</option>
                    <option value="daviplata">📲 Daviplata</option>
                    <option value="tarjeta">💳 Tarjeta</option>
                </select>
            </div>
            <div class="fila-total">
                <span>Total a pagar:</span>
                <strong id="total-carrito">$0</strong>
            </div>
            <button type="button" id="btn-confirmar-pedido" class="btn-principal" onclick="confirmarPedido()">Confirmar Pedido</button>
        </div>
    </aside>

    <script>
        let carrito = [];
        let filtroActual = 'todos';

        function cambiarVista(vista) {
            const vistas = {
                inicio: document.getElementById('vista-inicio'),
                restaurantes: document.getElementById('vista-restaurantes'),
                pedidos: document.getElementById('vista-pedidos')
            };
            const navs = {
                inicio: document.getElementById('nav-inicio'),
                restaurantes: document.getElementById('nav-restaurantes'),
                pedidos: document.getElementById('nav-pedidos')
            };

            Object.keys(vistas).forEach(key => {
                vistas[key].style.display = (key === vista) ? 'block' : 'none';
                navs[key].classList.toggle('activo', key === vista);
            });

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function abrirMenuRestaurante(idCategoria) {
            cambiarVista('inicio');
            filtroActual = idCategoria;
            document.querySelectorAll('.tarjeta-cat').forEach(b => b.classList.remove('activa'));
            const btnCat = document.getElementById('cat-btn-' + idCategoria);
            if (btnCat) btnCat.classList.add('activa');

            document.querySelectorAll('.card-rappi').forEach(card => {
                card.style.display = (card.dataset.categoria === idCategoria) ? 'flex' : 'none';
            });

            const seccionPopulares = document.getElementById('populares');
            if (seccionPopulares) {
                seccionPopulares.scrollIntoView({ behavior: 'smooth' });
            }
        }

        window.addEventListener('DOMContentLoaded', () => {
            if (window.location.hash === '#mis-pedidos') {
                cambiarVista('pedidos');
            }
        });

        function alternarMenuMovil() {
            document.getElementById('menu-navegacion').classList.toggle('mostrar-movil');
        }

        function cambiarDireccion() {
            const actual = document.getElementById('texto-direccion').textContent.replace(' ⌄', '');
            const nueva = prompt('Escribe tu dirección de entrega:', actual);
            if (nueva && nueva.trim() !== '') {
                document.getElementById('texto-direccion').textContent = nueva.trim() + ' ⌄';
            }
        }

        function filtrarCategoria(categoria, boton) {
            if (filtroActual === categoria) {
                filtroActual = 'todos';
                document.querySelectorAll('.tarjeta-cat').forEach(b => b.classList.remove('activa'));
            } else {
                filtroActual = categoria;
                document.querySelectorAll('.tarjeta-cat').forEach(b => b.classList.remove('activa'));
                boton.classList.add('activa');
            }
            document.querySelectorAll('.card-rappi').forEach(card => {
                card.style.display = (filtroActual === 'todos' || card.dataset.categoria === filtroActual) ? 'flex' : 'none';
            });
        }

        function agregarAlCarrito(id_producto, nombre, precio) {
            const existente = carrito.find(item => item.id_producto === id_producto);
            if (existente) {
                existente.cantidad++;
            } else {
                carrito.push({ id_producto, nombre, precio, cantidad: 1 });
            }
            actualizarVistaCarrito();
        }

        function quitarDelCarrito(indice) {
            carrito.splice(indice, 1);
            actualizarVistaCarrito();
        }

        function actualizarVistaCarrito() {
            const lista = document.getElementById('lista-carrito');
            const contador = document.getElementById('contador-carrito');
            const totalTexto = document.getElementById('total-carrito');
            let totalItems = 0;
            let totalPesos = 0;

            if (carrito.length === 0) {
                lista.innerHTML = '<p class="carrito-vacio">Aún no has agregado comida a tu carrito.</p>';
                contador.textContent = '0';
                totalTexto.textContent = '$0';
                return;
            }

            lista.innerHTML = '';
            carrito.forEach((item, index) => {
                totalItems += item.cantidad;
                totalPesos += item.precio * item.cantidad;
                lista.innerHTML += `
                    <div class="item-carrito">
                        <div>
                            <strong>${item.nombre}</strong>
                            <small>${item.cantidad} x $${item.precio.toLocaleString('es-CO')}</small>
                        </div>
                        <button type="button" class="btn-quitar" onclick="quitarDelCarrito(${index})">🗑️</button>
                    </div>
                `;
            });
            contador.textContent = totalItems;
            totalTexto.textContent = '$' + totalPesos.toLocaleString('es-CO');
        }

        function abrirCarrito() {
            document.getElementById('fondo-carrito').style.display = 'block';
            document.getElementById('panel-carrito').classList.add('abierto');
        }

        function cerrarCarrito() {
            document.getElementById('fondo-carrito').style.display = 'none';
            document.getElementById('panel-carrito').classList.remove('abierto');
        }

        async function confirmarPedido() {
            if (carrito.length === 0) {
                alert('Tu carrito está vacío. Agrega algún plato antes de pedir.');
                return;
            }
            const boton = document.getElementById('btn-confirmar-pedido');
            const direccion = document.getElementById('texto-direccion').textContent.replace(' ⌄', '').trim();
            const metodo_pago = document.getElementById('metodo-pago').value;

            boton.disabled = true;
            boton.textContent = 'Procesando pedido...';

            try {
                const respuesta = await fetch('guardar_pedido.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ items: carrito, direccion: direccion, metodo_pago: metodo_pago })
                });
                const resultado = await respuesta.json();
                if (resultado.ok) {
                    alert(resultado.mensaje);
                    window.location.href = 'inicio.php#mis-pedidos';
                    window.location.reload();
                } else {
                    alert('⚠️ ' + resultado.mensaje);
                }
            } catch (error) {
                alert('Error al conectar con el servidor XAMPP.');
            } finally {
                boton.disabled = false;
                boton.textContent = 'Confirmar Pedido';
            }
        }
    </script>
</body>
</html>
