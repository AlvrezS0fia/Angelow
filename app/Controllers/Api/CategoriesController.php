<?php
/**
 * ============================================================
 * ARCHIVO: CategoriesController.php — MÓDULO: API de categorías (OOP)
 * ============================================================
 * QUÉ HACE: CRUD de categorías usando patrón MVC con Modelo y Controller.
 *   Versión OOP del script legacy categories.php. Requiere sesión admin.
 * MODELO(S) QUE USA: CategoriaModel
 * ENDPOINTS/RUTAS: GET/POST /api/categorias, GET/PUT/DELETE /api/categorias/{id}
 * QUIÉN LO CONSUME: panel.js (gestión de categorías del administrador)
 */
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\CategoriaModel;

/**
 * Controlador OOP para categorías. Requiere autenticación de administrador.
 * Cada método delega la lógica de persistencia a CategoriaModel.
 */
class CategoriesController extends Controller {
    private $categoriaModel;

    public function __construct() {
        $this->categoriaModel = new CategoriaModel();
    }

    /**
     * Verifica sesión de administrador. Si no, retorna 403 JSON y sale.
     */
    private function checkAdmin() {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            http_response_code(403);
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }
    }

    /**
     * GET /api/categorias — Lista todas las categorías y subcategorías.
     */
    public function index() {
        $this->checkAdmin();
        $categorias = $this->categoriaModel->getAll();
        $subcategorias = $this->categoriaModel->getAllWithSub();

        echo json_encode([
            'categorias' => $categorias,
            'subcategorias' => $subcategorias
        ]);
    }

    /**
     * GET /api/categorias/{id} — Detalle de una categoría específica.
     */
    public function show($id) {
        $this->checkAdmin();
        $categoria = $this->categoriaModel->getById($id);

        if (!$categoria) {
            http_response_code(404);
            echo json_encode(['error' => 'Categoría no encontrada']);
            return;
        }

        echo json_encode($categoria);
    }

    /**
     * POST /api/categorias — Crea una nueva categoría. Nombre es obligatorio.
     */
    public function store() {
        $this->checkAdmin();
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['nombre'])) {
            http_response_code(400);
            echo json_encode(['error' => 'El nombre es requerido']);
            return;
        }

        $result = $this->categoriaModel->create($data);

        if ($result) {
            echo json_encode(['success' => true, 'id' => $result['id'], 'message' => 'Categoría creada']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al crear categoría']);
        }
    }

    /**
     * PUT /api/categorias/{id} — Actualiza los datos de una categoría existente.
     */
    public function update($id) {
        $this->checkAdmin();
        $data = json_decode(file_get_contents('php://input'), true);

        $existing = $this->categoriaModel->getById($id);
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['error' => 'Categoría no encontrada']);
            return;
        }

        $result = $this->categoriaModel->update($id, $data);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Categoría actualizada']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar categoría']);
        }
    }

    /**
     * DELETE /api/categorias/{id} — Elimina una categoría si existe.
     */
    public function destroy($id) {
        $this->checkAdmin();
        $existing = $this->categoriaModel->getById($id);
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['error' => 'Categoría no encontrada']);
            return;
        }

        $result = $this->categoriaModel->delete($id);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Categoría eliminada']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar categoría']);
        }
    }
}
