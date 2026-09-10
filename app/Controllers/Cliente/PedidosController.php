<?php
/**
 * ============================================================
 * ARCHIVO: PedidosController.php — MÓDULO: API de pedidos del cliente
 * ============================================================
 * QUÉ HACE: API de los pedidos del cliente autenticado: listar sus pedidos,
 *   ver el detalle de uno, cancelarlo y consultar el seguimiento en tiempo real
 *   (ubicación GPS del repartidor). Todas las respuestas son JSON.
 * MODELO(S) QUE USA: PedidoModel, Database (App\Core), UsuarioModel (importado)
 * ENDPOINTS/RUTAS: GET /api/mis-pedidos, GET /api/mis-pedidos/{id},
 *   POST /api/mis-pedidos/{id}/cancelar, GET /api/mis-pedidos/{id}/seguimiento
 * QUIÉN LO CONSUME: Vista "mis pedidos" y mapa de seguimiento del cliente (fetch/fetch() en JS).
 */
namespace App\Controllers\Cliente;

use App\Core\Controller;
use App\Core\Database;
use App\Models\PedidoModel;
use App\Models\UsuarioModel;

// HERENCIA: controlador API de cliente que hereda la respuesta JSON de la base.
class PedidosController extends Controller
{
    /** Instancia del modelo de pedidos. */
    private PedidoModel $pedidoModel;

    public function __construct() {
        $this->pedidoModel = new PedidoModel();
    }
    /** Lista los pedidos del cliente logueado (el id sale de la sesión). */
    public function index() {
        if (!isset($_SESSION['user'])) {
            $this->json(['error' => 'No autorizado'], 403);
            return;
        }
        // El id se toma de la SESIÓN, nunca del body/URL. Así el usuario solo
        // puede leer sus propios pedidos (el frontend no puede elegir otro id).
        $usuarioId = (int) $_SESSION['user']['id'];
        $pedidos = $this->pedidoModel->getByUsuario($usuarioId);

        $this->json($pedidos);
    }

    // GET /api/mis-pedidos/{id} → Detalle de UN pedido propio.
    //   ↓ Entrada: $pedidoId viene de la URL (parámetro routeado por el Router).
    //   ↓ Validación de rol: sesión obligatoria.
    //   ↓ Validación de propiedad (IDOR): $pedido['usuario_id'] debe ser el de
    //     la sesión; si no → 404. Esto impide leer el pedido de otro cliente.
    //   ↓ Se procesan en: PedidoModel::getById + getDetalles
    //   ↓ Retorna: JSON {pedido, items} → frontend.
    /** @param int|string $pedidoId */
    public function detalle($pedidoId) {
        if (!isset($_SESSION['user'])) {
            $this->json(['error' => 'No autorizado'], 403);
            return;
        }
        $pedido = $this->pedidoModel->getById($pedidoId);
        if (!$pedido || $pedido['usuario_id'] != $_SESSION['user']['id']) {
            $this->json(['error' => 'Pedido no encontrado'], 404);
            return;
        }
        $items = $this->pedidoModel->getDetalles($pedidoId);
        $this->json([
            'pedido' => $pedido,
            'items' => $items
        ]);
    }

    // POST /api/mis-pedidos/{id}/cancelar → Cancela un pedido propio.
    //   ↓ Entrada: $pedidoId desde la URL + $_SESSION (identidad del cliente).
    //   ↓ Validaciones en cadena:
    //       1. Sesión obligatoria (403).
    //       2. Propiedad del pedido (404 si no es suyo).
    //       3. Estado permitido: solo 'pendiente' o 'procesando' (400 si no).
    //   ↓ Se guarda en: tabla `pedidos` (UPDATE estado = 'cancelado')
    //     vía PedidoModel::updateEstado.
    //   ↓ Retorna: JSON {success} → frontend refresca la vista de mis pedidos.
    /** @param int|string $pedidoId */
    public function cancelar($pedidoId) {
        if (!isset($_SESSION['user'])) {
            $this->json(['error' => 'No autorizado'], 403);
            return;
        }
        $pedido = $this->pedidoModel->getById($pedidoId);
        if (!$pedido || $pedido['usuario_id'] != $_SESSION['user']['id']) {
            $this->json(['error' => 'Pedido no encontrado'], 404);
            return;
        }
        $estado = strtolower($pedido['estado'] ?? '');
        if (!in_array($estado, ['pendiente'])) {
            $this->json(['error' => 'Solo se pueden cancelar pedidos pendientes'], 400);
            return;
        }
        $this->pedidoModel->updateEstado($pedidoId, 'rechazada');
        $this->json(['success' => true, 'message' => 'Pedido cancelado correctamente']);
    }

    // GET /api/mis-pedidos/{id}/seguimiento → Ubicación en tiempo real del pedido.
    //   ↓ Entrada: $pedidoId desde la URL + $_SESSION.
    //   ↓ Validaciones: sesión obligatoria + propiedad del pedido (IDOR).
    //   ↓ Procesamiento (3 orígenes de datos):
    //       1. tabla `pedidos` → estado, número, dirección, coordenadas destino.
    //       2. tabla `seguimiento_tiempo_real` → última ubicación GPS (lat, long,
    //          velocidad, batería). Es la posicion que el repartidor reportó vía
    //          POST /api/repartidor/seguimiento/ubicacion.
    //       3. tabla `usuarios` → datos del repartidor asignado (repartidor_id).
    //   ↓ Retorna a: fetch() del mapa en el perfil/cliente (JSON).
    /** @param int|string $pedidoId */
    public function seguimiento($pedidoId) {
        if (!isset($_SESSION['user'])) {
            $this->json(['error' => 'No autorizado'], 403);
            return;
        }
        $usuarioId = (int) $_SESSION['user']['id'];
        $pedido = $this->pedidoModel->getById($pedidoId);
        if (!$pedido || (int)$pedido['usuario_id'] !== $usuarioId) {
            $this->json(['error' => 'Pedido no encontrado'], 404);
            return;
        }

        $tracking = Database::query(
            "SELECT * FROM seguimiento_tiempo_real WHERE pedido_id = ? ORDER BY timestamp_ubicacion DESC LIMIT 1",
            [$pedidoId]
        )->fetch();

        $repartidor = null;
        if (!empty($pedido['repartidor_id'])) {
            $repartidor = Database::query(
                "SELECT id, nombre, telefono FROM usuarios WHERE id = ?",
                [$pedido['repartidor_id']]
            )->fetch();
        }

        $this->json([
            'success' => true,
            'pedido' => [
                'id' => (int) $pedido['id'],
                'numero_pedido' => $pedido['numero_pedido'],
                'estado' => $pedido['estado'],
                'fecha_pedido' => $pedido['fecha_pedido'],
                'direccion_envio' => $pedido['direccion_envio'] ?? '',
                'latitud_destino' => isset($pedido['latitud_destino']) ? floatval($pedido['latitud_destino']) : null,
                'longitud_destino' => isset($pedido['longitud_destino']) ? floatval($pedido['longitud_destino']) : null,
                'metodo_envio' => $pedido['metodo_envio'] ?? '',
            ],
            'ubicacion' => $tracking ? [
                'latitud' => floatval($tracking['latitud']),
                'longitud' => floatval($tracking['longitud']),
                'velocidad_kmh' => floatval($tracking['velocidad_kmh'] ?? 0),
                'bateria_porcentaje' => intval($tracking['bateria_porcentaje'] ?? 0),
                'timestamp' => $tracking['timestamp_ubicacion'],
            ] : null,
            'repartidor' => $repartidor ? [
                'id' => (int) $repartidor['id'],
                'nombre' => $repartidor['nombre'] ?? '',
                'telefono' => $repartidor['telefono'] ?? '',
            ] : null,
        ]);
    }
}