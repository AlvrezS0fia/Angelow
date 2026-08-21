<?php
namespace App\Controllers\Cliente;

use App\Core\Controller;
use App\Models\DireccionModel;

class DireccionController extends Controller {
    private $model;

    public function __construct() {
        $this->model = new DireccionModel();
    }

    private function requireAuth() {
        if (!isset($_SESSION['user'])) { $this->json(['error' => 'No autorizado'], 401); }
        return (int)$_SESSION['user']['id'];
    }

    public function index() {
        $uid = $this->requireAuth();
        $direcciones = $this->model->getByUsuario($uid);
        $this->json(['success' => true, 'direcciones' => $direcciones]);
    }

    public function crear() {
        $uid = $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
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

    public function eliminar($id) {
        $uid = $this->requireAuth();
        $result = $this->model->eliminar($id, $uid);
        if ($result) {
            $this->json(['success' => true, 'message' => 'Dirección eliminada']);
        } else {
            $this->json(['error' => 'Dirección no encontrada'], 404);
        }
    }

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
