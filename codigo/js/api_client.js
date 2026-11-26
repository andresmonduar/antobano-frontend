/**
 * Módulo de Integración Frontend - API REST AntoBano
 * Conecta las vistas HTML/PHP con los servicios web en /api
 */
const AntoBanoAPI = {
    baseURL: "api",

    async obtenerMenu() {
        const res = await fetch(`${this.baseURL}/productos.php`);
        return await res.json();
    },

    async registrarCliente(datos) {
        const res = await fetch(`${this.baseURL}/registro.php`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(datos)
        });
        return { status: res.status, data: await res.json() };
    },

    async iniciarSesion(correo, contrasena) {
        const res = await fetch(`${this.baseURL}/login.php`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ correo, contrasena })
        });
        return { status: res.status, data: await res.json() };
    },

    async crearPedido(pedido) {
        const res = await fetch(`${this.baseURL}/pedidos.php`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(pedido)
        });
        return { status: res.status, data: await res.json() };
    }
};
