<?php
/**
 * ============================================================
 * ARCHIVO: ProductoMicroservice.php — MÓDULO: Cliente PHP del microservicio
 * ============================================================
 * QUÉ HACE: Cliente HTTP (cURL) que conecta El panel admin de ANGELOW con el
 *   microservicio de PRODUCTOS (Spring Boot, puerto 8082). TODAS las operaciones
 *   sobre la tabla angelow_db.productos pasan por AQUÍ: el PHP jamás consulta
 *   MySQL directamente para productos.
 * URL BASE: usa la constante PRODUCTOS_API_URL (definida en config/app.php,
 *   leída del .env). Centralizada: si cambia la URL del microservicio, se
 *   actualiza en un solo lugar.
* ENDPOINTS QUE CONSUME (del microservicio):
 *   GET    {URL}                    -> obtenerProductos()
 *   GET    {URL}?page=&size=        -> obtenerProductosPaginados()
 *   GET    {URL}/{id}               -> obtenerProducto()
 *   GET    {URL}/buscar/and         -> buscarAND()
 *   GET    {URL}/buscar/or          -> buscarOR()
 *   POST   {URL}                    -> crearProducto()
 *   PUT    {URL}/{id}               -> actualizarProducto()
 *   DELETE {URL}/{id}               -> eliminarProducto()
 * QUIÉN LO USA: Inventario2Controller (pestaña Inventario2 del panel admin).
 * ============================================================
 */
namespace App\Libraries;

/**
 * Cliente del microservicio de productos del proyecto ANGELOW.
 * Cada método devuelve un array con la respuesta del microservicio
 * (o del mensaje de error si el microservicio no responde).
 */
class ProductoMicroservice
{
    /** @var string URL base del microservicio (sin barra final). */
    private string $baseUrl;

    public function __construct()
    {
        // Se toma la URL del microservicio desde la constante centralizada
        // config/app.php (que lee PRODUCTOS_API_URL del .env).
        $this->baseUrl = rtrim(constant('PRODUCTOS_API_URL'), '/');
    }

    // ============================================================
    // LISTAR TODOS - GET {URL}
    // ============================================================
    public function obtenerProductos(): array
    {
        return $this->peticion('GET', '');
    }

    // ============================================================
    // LISTAR PAGINADO - GET {URL}?page=&size=
    // ============================================================
    public function obtenerProductosPaginados(int $page = 0, int $size = 10): array
    {
        return $this->peticion('GET', '?page=' . $page . '&size=' . $size);
    }

    // ============================================================
    // OBTENER UNO POR ID - GET {URL}/{id}
    // ============================================================
    public function obtenerProducto(int $id): array
    {
        return $this->peticion('GET', '/' . $id);
    }

    // ============================================================
    // CREAR - POST {URL}
    // ============================================================
    public function crearProducto(array $datos): array
    {
        return $this->peticion('POST', '', $datos);
    }

    // ============================================================
    // ACTUALIZAR - PUT {URL}/{id}
    // ============================================================
    public function actualizarProducto(int $id, array $datos): array
    {
        return $this->peticion('PUT', '/' . $id, $datos);
    }

    // ============================================================
    // ELIMINAR - DELETE {URL}/{id}
    // ============================================================
    public function eliminarProducto(int $id): array
    {
        return $this->peticion('DELETE', '/' . $id);
    }

    // ============================================================
    // BÚSQUEDA AND (2 campos) - GET {URL}/buscar/and?nombre=&estado=
    // Ambas condiciones deben cumplirse: nombre Y estado (RETO 1).
    // El microservicio valida que estado sea DISPONIBLE|BAJO|AGOTADO.
    // ============================================================
    public function buscarAND(string $nombre, string $estado, int $page = 0, int $size = 10): array
    {
        $query = http_build_query([
            'nombre' => $nombre,
            'estado' => $estado,
            'page'   => $page,
            'size'   => $size,
        ]);
        return $this->peticion('GET', '/buscar/and?' . $query);
    }

    // ============================================================
    // BÚSQUEDA OR (3 campos) - GET {URL}/buscar/or?valor=
    // ============================================================
    public function buscarOR(string $valor, int $page = 0, int $size = 10): array
    {
        $query = http_build_query([
            'valor' => $valor,
            'page'  => $page,
            'size'  => $size,
        ]);
        return $this->peticion('GET', '/buscar/or?' . $query);
    }

    // ============================================================
    // PETICIÓN HTTP INTERNA
    // ------------------------------------------------------------
    // Usa cURL (extensión estándar de PHP) para hablar con el
    // microservicio. Devuelve SIEMPRE un array:
    //   - Si el microservicio responde: el JSON decodificado.
    //   - Si falla la conexión: ['error' => ...] con el detalle.
    // ============================================================
    private function peticion(string $metodo, string $uri, array $datos = []): array
    {
        $url = $this->baseUrl . $uri;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $metodo,
            CURLOPT_TIMEOUT        => 10,
            // El microservicio responde JSON siempre; los errores como 404/400
            // también traen un cuerpo JSON que debemos leer.
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);

        // En POST/PUT se envía el cuerpo en JSON
        if (in_array($metodo, ['POST', 'PUT'], true)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Content-Type: application/json',
            ]);
        }

        $respuesta  = curl_exec($ch);
        $estado     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errorCurl  = curl_error($ch);
        curl_close($ch);

        // El DELETE devuelve 204 sin cuerpo: se normaliza a éxito.
        if ($respuesta === false) {
            return ['error' => 'No se pudo conectar con el microservicio: ' . ($errorCurl ?? 'desconocido')];
        }
        if ($metodo === 'DELETE' && $estado === 204 && $respuesta === '') {
            return ['success' => true, 'mensaje' => 'Producto eliminado'];
        }

        $decodificado = json_decode($respuesta, true);
        if (is_array($decodificado)) {
            // Se conserva el código HTTP real por si el controller lo necesita
            $decodificado['_http_status'] = $estado;
            return $decodificado;
        }

        return ['error' => 'Respuesta inesperada del microservicio', '_http_status' => $estado];
    }
}