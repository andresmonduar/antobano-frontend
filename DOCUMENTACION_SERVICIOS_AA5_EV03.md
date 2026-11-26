# Documentación de Servicios Web (API REST) - Proyecto AntoBano
Evidencia: GA7-220501096-AA5-EV03 - Diseño y desarrollo de servicios web - proyecto
Aprendiz: Andrés Santiago Moncada Duarte
Tecnologías: PHP 8.2 (PDO), MySQL (antobano_db), Apache (XAMPP), formato JSON
URL Base Local: http://localhost/antobano/api

## 1. Arquitectura y Conexión (api/config/conexion.php)
Todos los endpoints devuelven cabeceras Content-Type: application/json; charset=UTF-8 y utilizan consultas preparadas mediante PDO contra la base de datos relacional antobano_db (usuarios, productos, categorias, pedidos, detalle_pedido).

## 2. Catálogo de Servicios Desarrollados

### 2.1. Registro de Clientes
- Endpoint: /registro.php
- Método: POST
- Descripción: Registra un cliente nuevo en la tabla usuarios, valida que el correo no esté duplicado y cifra la contraseña con password_hash (PASSWORD_BCRYPT).
- Petición JSON:
    {
      "nombre": "Andrés Moncada",
      "correo": "cliente.nuevo@antobano.com",
      "telefono": "3101234567",
      "direccion_principal": "Av. Libertadores # 15-40, Cúcuta",
      "contrasena": "claveSegura123"
    }
- Respuestas HTTP:
  - 201 Created: Cliente registrado correctamente (devuelve id_usuario).
  - 400 Bad Request: Campos obligatorios faltantes o correo inválido.
  - 409 Conflict: El correo ya está registrado.

### 2.2. Autenticación / Inicio de Sesión
- Endpoint: /login.php
- Método: POST
- Descripción: Verifica las credenciales comparando la contraseña enviada contra el hash guardado en MySQL mediante password_verify.
- Petición JSON:
    {
      "correo": "cliente.nuevo@antobano.com",
      "contrasena": "claveSegura123"
    }
- Respuestas HTTP:
  - 200 OK: Autenticación satisfactoria (devuelve id_usuario, nombre, correo y rol).
  - 401 Unauthorized: Credenciales incorrectas.

### 2.3. Gestión de Menú y Productos
- Endpoint: /productos.php
- Métodos: GET y POST
- Descripción: GET lista los productos disponibles cruzando su categoría desde categorias. POST agrega un nuevo producto al menú.
- Petición POST JSON:
    {
      "id_categoria": 1,
      "nombre": "Hamburguesa Especial AntoBano",
      "descripcion": "Doble carne artesanal, queso cheddar, tocineta y salsa de la casa",
      "precio": 25000
    }
- Respuestas HTTP:
  - 200 OK (GET): Devuelve el listado de productos disponibles.
  - 201 Created (POST): Producto agregado al menú (devuelve id_producto).
  - 400 Bad Request: Nombre vacío o precio inválido.

### 2.4. Gestión de Pedidos
- Endpoint: /pedidos.php
- Métodos: GET y POST
- Descripción: GET consulta el historial de pedidos con el nombre del cliente. POST registra un pedido y sus productos dentro de una transacción SQL (pedidos + detalle_pedido), calculando el total automáticamente.
- Petición POST JSON:
    {
      "id_cliente": 1,
      "direccion_entrega": "Calle 10 # 4-25, Centro",
      "metodo_pago": "nequi",
      "items": [
        { "id_producto": 1, "cantidad": 2 },
        { "id_producto": 5, "cantidad": 1 }
      ]
    }
- Respuestas HTTP:
  - 200 OK (GET): Devuelve el listado general de pedidos.
  - 201 Created (POST): Pedido registrado exitosamente (devuelve id_pedido y total).
  - 400 Bad Request: Datos incompletos, método de pago inválido o producto no disponible.
