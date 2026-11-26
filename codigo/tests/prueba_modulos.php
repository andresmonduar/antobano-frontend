<?php
/**
 * Pruebas Unitarias y de Integración de Módulos - AntoBano
 */
function probarEndpoint($metodo, $url, $payload = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($metodo === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }
    $respuesta = curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['codigo' => $codigo, 'body' => json_decode($respuesta, true)];
}

$base = "http://localhost/antobano/api";
echo "=== PRUEBAS DE MODULOS INTEGRADOS ANTOBANO ===\n";

$p1 = probarEndpoint('GET', "$base/productos.php");
echo "[1] Modulo Catalogo (GET /productos.php): HTTP {$p1['codigo']} -> " . ($p1['body']['exito'] ? "APROBADO" : "FALLO") . "\n";

$p2 = probarEndpoint('POST', "$base/login.php", ['correo' => 'inexistente@antobano.com', 'contrasena' => '000000']);
echo "[2] Modulo Seguridad Login (POST /login.php clave invalida): HTTP {$p2['codigo']} -> " . ($p2['codigo'] === 401 ? "APROBADO" : "FALLO") . "\n";

$p3 = probarEndpoint('GET', "$base/pedidos.php");
echo "[3] Modulo Pedidos (GET /pedidos.php): HTTP {$p3['codigo']} -> " . ($p3['body']['exito'] ? "APROBADO" : "FALLO") . "\n";
