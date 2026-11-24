<?php
foreach (['ANTOBANO_CLIENTE', 'ANTOBANO_ADMINISTRADOR', 'ANTOBANO_DOMICILIARIO', 'PHPSESSID'] as $nombre) {
    if (isset($_COOKIE[$nombre])) {
        setcookie($nombre, '', time() - 3600, '/');
    }
}
header("Location: login.html");
exit;
?>
