<?php
/**
 * ============================================================
 * ARCHIVO: DireccionController.php — MÓDULO: API de direcciones del cliente
 * ============================================================
 * QUÉ HACE: CRUD de direcciones de envío del cliente autenticado (listar, crear,
 *   actualizar, eliminar y marcar como predeterminada), siempre respondiendo JSON.
 * MODELO(S) QUE USA: DireccionModel
 * ENDPOINTS/RUTAS: GET/POST /api/direcciones, PUT/DELETE /api/direcciones/{id},
 *   POST /api/direcciones/{id}/predeterminada
 * QUIÉN LO CONSUME: El checkout y el perfil del cliente (fetch() desde JS).
 */
namespace App\Controllers\Cliente;

use App\Core\Controller;
use App\Models\DireccionModel;

/**
 * Controlador API de direcciones del cliente. Extiende la clase base Controller.
 * Patrón MVC: cada acción exige sesión (requireAuth) y delega en DireccionModel.
 */
class DireccionController extends Controller {
    /** Instancia del modelo de direcciones. */
    private $model;

    public function __construct() {
        $this->model = new DireccionModel();
    }

    /**
     * Verifica que haya sesión; si no, responde 401. Devuelve el id del usuario.
     * Uso: primera línea de cada acción para obtener la identidad del cliente.
     */
    private function requireAuth() {
        if (!isset($_SESSION['user'])) { $this->json(['error' => 'No autorizado'], 401); }
        return (int)$_SESSION['user']['id'];
    }

    /** Lista todas las direcciones del cliente logueado. */
    public function index() {
        $uid = $this->requireAuth();
        $direcciones = $this->model->getByUsuario($uid);
        $this->json(['success' => true, 'direcciones' => $direcciones]);
    }

    /** Crea una dirección validando que los campos obligatorios vengan en el JSON. */
    public function crear() {
        $uid = $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        // Validación de campos requeridos antes de insertar.
        if (empty($input['departamento']) || empty($input['municipio']) || empty($input['calle']) || empty($input['barrio']) || empty($input['destinatario'])) {
            $this->json(['error' => 'Campos requeridos faltantes'], 400);
            return;
        }
        $id = $this->model->crear($uid, $input);
        if ($id) {
            $this->json(['success' => true, 'id' => $id, 'message' => 'Dirección creada']);
        } else {
            $this->json(['error' => 'Error al crear dirección'], 500);
        }
    }

    /** Actualiza una dirección siempre que pertenezca al cliente (por id de usuario). */
    public function actualizar($id) {
        $uid = $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $result = $this->model->actualizar($id, $uid, $input);
        if ($result) {
            $this->json(['success' => true, 'message' => 'Dirección actualizada']);
        } else {
            $this->json(['error' => 'Dirección no encontrada'], 404);
        }
    }

    /** Elimina una dirección del cliente (solo si le pertenece). */
    public function eliminar($id) {
        $uid = $this->requireAuth();
        $result = $this->model->eliminar($id, $uid);
        if ($result) {
            $this->json(['success' => true, 'message' => 'Dirección eliminada']);
        } else {
            $this->json(['error' => 'Dirección no encontrada'], 404);
        }
    }

    /** Marca una dirección del cliente como predeterminada. */
    public function setPredeterminada($id) {
        $uid = $this->requireAuth();
        $result = $this->model->setPredeterminada($id, $uid);
        if ($result) {
            $this->json(['success' => true, 'message' => 'Dirección predeterminada actualizada']);
        } else {
            $this->json(['error' => 'Dirección no encontrada'], 404);
        }
    }
}
