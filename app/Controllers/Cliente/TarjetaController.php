<?php
namespace App\Controllers\Cliente;

use App\Core\Controller;
use App\Models\TarjetaModel;

class TarjetaController extends Controller {
    private $model;

    public function __construct() {
        $this->model = new TarjetaModel();
    }

    private function requireAuth() {
        if (!isset($_SESSION['user'])) { $this->json(['error' => 'No autorizado'], 401); }
        return (int)$_SESSION['user']['id'];
    }

    public function index() {
        $uid = $this->requireAuth();
        $tarjetas = $this->model->getByUsuario($uid);
        $this->json(['success' => true, 'tarjetas' => $tarjetas]);
    }

    public function crear() {
        $uid = $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['numero_tarjeta']) || empty($input['titular']) || empty($input['mes_expiracion']) || empty($input['anio_expiracion']) || empty($input['departamento']) || empty($input['municipio']) || empty($input['calle']) || empty($input['barrio']) || empty($input['codigo_postal'])) {
            $this->json(['error' => 'Campos requeridos faltantes'], 400);
            return;
        }

        $numLimpio = preg_replace('/\s/', '', $input['numero_tarjeta']);
        if (strlen($numLimpio) < 13 || strlen($numLimpio) > 19 || !preg_match('/^\d+$/', $numLimpio)) {
            $this->json(['error' => 'Número de tarjeta inválido'], 400);
            return;
        }

        $id = $this->model->crear($uid, $input);
        if ($id) {
            $this->json(['success' => true, 'id' => $id, 'message' => 'Tarjeta guardada']);
        } else {
            $this->json(['error' => 'Error al guardar tarjeta'], 500);
        }
    }

    public function actualizar($id) {
        $uid = $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $result = $this->model->actualizar($id, $uid, $input);
        if ($result) {
            $this->json(['success' => true, 'message' => 'Tarjeta actualizada']);
        } else {
            $this->json(['error' => 'Tarjeta no encontrada'], 404);
        }
    }

    public function eliminar($id) {
        $uid = $this->requireAuth();
        $result = $this->model->eliminar($id, $uid);
        if ($result) {
            $this->json(['success' => true, 'message' => 'Tarjeta eliminada']);
        } else {
            $this->json(['error' => 'Tarjeta no encontrada'], 404);
        }
    }

    public function setPredeterminada($id) {
        $uid = $this->requireAuth();
        $result = $this->model->setPredeterminada($id, $uid);
        if ($result) {
            $this->json(['success' => true, 'message' => 'Tarjeta predeterminada actualizada']);
        } else {
            $this->json(['error' => 'Tarjeta no encontrada'], 404);
        }
    }
}
