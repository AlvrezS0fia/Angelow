<?php
/**
 * ============================================================
 * ARCHIVO: StockController.php — MÓDULO: API de inventario (admin)
 * ============================================================
 * QUÉ HACE: CRUD de inventario para el panel administrativo.
 *   Ahora TODOS los datos salen del MICROSERVICIO de inventario
 *   (Spring Boot + JPA, puerto 8082, tabla angelow_db.inventario)
 *   usando el cliente App\Libraries\ProductoMicroservice. El PHP
 *   ya NO consulta MySQL directamente para el inventario.
 * MODELO(S) QUE USA: App\Libraries\ProductoMicroservice.
 * ENDPOINTS/RUTAS: GET /api/inventario, PUT /api/inventario/{id},
 *   POST /api/inventario/{id}/ajustar, DELETE /api/inventario
 * QUIÉN LO CONSUME: admin/inventario.php (vista principal)
 * ============================================================
 */
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Libraries\ProductoMicroservice;

/**
 * Controlador de gestión de stock para el panel administrativo.
 * Valida que cada acción requiera sesión de administrador y delega
 * en el microservicio de inventario.
 */
class StockController extends Controller
{
    /**
     * GET /api/inventario — Lista todos los registros de inventario
     * obtenidos desde el microservicio (angelow_db.inventario).
     */
    public function index()
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            http_response_code(403);
            echo json_encode(['error' => 'No autorizado']);
            return;
        }

        $cliente = new ProductoMicroservice();
        $respuesta = $cliente->obtenerProductos();

        // Si el microservicio no responde, se devuelve un arreglo vacio
        if (isset($respuesta['error'])) {
            http_response_code(502);
            echo json_encode(['error' => $respuesta['error']]);
            return;
        }

        $resultado = [];
        foreach ($respuesta as $inv) {
            // Ignora la clave meta '_http_status' y cualquier entrada no-producto
            if (!is_array($inv) || !isset($inv['id'])) {
                continue;
            }
            $resultado[] = [
                'id'          => (int) ($inv['id'] ?? 0),
                'nombre'      => $inv['nombre'] ?? '',
                'categoria'   => $inv['categoria'] ?? '',
                'precio'      => (float) ($inv['precio'] ?? 0),
                'stock_total' => (int) ($inv['stockTotal'] ?? $inv['stock_total'] ?? 0),
                'stock_minimo' => (int) ($inv['stockMinimo'] ?? $inv['stock_minimo'] ?? 0),
                'estado'      => $inv['estado'] ?? 'DISPONIBLE',
                'imagen'      => '',
                'vendidos'    => 0,
            ];
        }

        echo json_encode($resultado);
    }

    /**
     * PUT /api/inventario/{id} — Actualiza el stock de un registro
     * de inventario en el microservicio.
     */
    public function update($id)
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            http_response_code(403);
            echo json_encode(['error' => 'No autorizado']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['stock'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Stock no proporcionado']);
            return;
        }

        $nuevoStock = (int) $data['stock'];
        if ($nuevoStock < 0) {
            http_response_code(400);
            echo json_encode(['error' => 'El stock no puede ser negativo']);
            return;
        }

        $cliente = new ProductoMicroservice();
        $actual = $cliente->obtenerProducto((int) $id);

        if (isset($actual['error'])) {
            http_response_code(404);
            echo json_encode(['error' => 'Registro de inventario no encontrado']);
            return;
        }

        $resultado = $cliente->actualizarProducto((int) $id, [
            'productoId'  => (int) ($actual['productoId'] ?? 0),
            'nombre'      => $actual['nombre'] ?? '',
            'categoria'   => $actual['categoria'] ?? '',
            'stockTotal'  => $nuevoStock,
            'stockMinimo' => (int) ($actual['stockMinimo'] ?? 0),
            'precio'      => (float) ($actual['precio'] ?? 0),
        ]);

        if (isset($resultado['error'])) {
            http_response_code(502);
            echo json_encode(['error' => $resultado['error']]);
            return;
        }

        echo json_encode(['success' => true, 'nuevo_stock' => $nuevoStock]);
    }

    /**
     * POST /api/inventario/{id}/ajustar — Ajusta stock con operación
     * aritmética (add/subtract/set) delegando en el microservicio.
     */
    public function ajustar($id)
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            http_response_code(403);
            echo json_encode(['error' => 'No autorizado']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['cantidad']) || !isset($data['tipo'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Datos incompletos']);
            return;
        }

        $cantidad = (int) $data['cantidad'];
        $tipo = $data['tipo']; // 'add', 'subtract', 'set'

        $cliente = new ProductoMicroservice();
        $actual = $cliente->obtenerProducto((int) $id);

        if (isset($actual['error'])) {
            http_response_code(404);
            echo json_encode(['error' => 'Registro de inventario no encontrado']);
            return;
        }

        $stockActual = (int) ($actual['stockTotal'] ?? 0);
        $nuevoStock = $stockActual;

        if ($tipo === 'add') {
            $nuevoStock = $stockActual + $cantidad;
        } elseif ($tipo === 'subtract') {
            $nuevoStock = max(0, $stockActual - $cantidad);
        } elseif ($tipo === 'set') {
            $nuevoStock = $cantidad;
        }

        $resultado = $cliente->actualizarProducto((int) $id, [
            'productoId'  => (int) ($actual['productoId'] ?? 0),
            'nombre'      => $actual['nombre'] ?? '',
            'categoria'   => $actual['categoria'] ?? '',
            'stockTotal'  => $nuevoStock,
            'stockMinimo' => (int) ($actual['stockMinimo'] ?? 0),
            'precio'      => (float) ($actual['precio'] ?? 0),
        ]);

        if (isset($resultado['error'])) {
            http_response_code(502);
            echo json_encode(['error' => $resultado['error']]);
            return;
        }

        echo json_encode(['success' => true, 'nuevo_stock' => $nuevoStock, 'stock_anterior' => $stockActual]);
    }

    /**
     * DELETE /api/inventario — Elimina un registro de inventario en
     * el microservicio (el id se recibe en el body JSON).
     */
    public function destroy()
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            http_response_code(403);
            echo json_encode(['error' => 'No autorizado']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? 0;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID del registro requerido']);
            return;
        }

        $cliente = new ProductoMicroservice();
        $resultado = $cliente->eliminarProducto((int) $id);

        if (isset($resultado['error'])) {
            http_response_code(502);
            echo json_encode(['error' => $resultado['error']]);
            return;
        }

        echo json_encode(['success' => true, 'message' => 'Registro de inventario eliminado correctamente']);
    }
}