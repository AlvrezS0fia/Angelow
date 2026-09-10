<?php
/**
 * ============================================================
 * ARCHIVO: TarjetaController.php — MÓDULO: API de tarjetas de pago del cliente
 * ============================================================
 * QUÉ HACE: CRUD de tarjetas del cliente autenticado sobre su cuenta de pago
 *   (listar, crear con validación del número, actualizar, eliminar y marcar
 *   predeterminada). Siempre responde JSON.
 * MODELO(S) QUE USA: TarjetaModel
 * ENDPOINTS/RUTAS: GET/POST /api/tarjetas, PUT/DELETE /api/tarjetas/{id},
 *   POST /api/tarjetas/{id}/predeterminada
 * QUIÉN LO CONSUME: El checkout y el perfil del cliente (fetch() desde JS).
 */
namespace App\Controllers\Cliente;

use App\Core\Controller;
use App\Models\TarjetaModel;

/**
 * Controlador API de tarjetas del cliente. Extiende la clase base Controller.
 * Patrón MVC: cada acción exige sesión (requireAuth) y delega en TarjetaModel.
 */
class TarjetaController extends Controller {
    /** Instancia del modelo de tarjetas. */
    private $model;

    public function __construct() {
        $this->model = new TarjetaModel();
    }

    /** Verifica sesión y devuelve el id del cliente; si no, responde 401. */
    private function requireAuth() {
        if (!isset($_SESSION['user'])) { $this->json(['error' => 'No autorizado'], 401); }
        return (int)$_SESSION['user']['id'];
    }

    /** Lista las tarjetas guardadas del cliente logueado. */
    public function index() {
        $uid = $this->requireAuth();
        $tarjetas = $this->model->getByUsuario($uid);
        $this->json(['success' => true, 'tarjetas' => $tarjetas]);
    }

    /** Guarda una tarjeta validando campos obligatorios y el formato del número. */
    public function crear() {
        $uid = $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);

        // Validación de campos obligatorios de la tarjeta y la dirección de facturación.
        if (empty($input['numero_tarjeta']) || empty($input['titular']) || empty($input['mes_expiracion']) || empty($input['anio_expiracion']) || empty($input['departamento']) || empty($input['municipio']) || empty($input['calle']) || empty($input['barrio']) || empty($input['codigo_postal'])) {
            $this->json(['error' => 'Campos requeridos faltantes'], 400);
            return;
        }

        // Se quitan espacios y se valida longitud (13-19 dígitos) y que sea numérico.
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

    /** Actualiza una tarjeta del cliente (solo si le pertenece). */
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

    /** Elimina una tarjeta del cliente (solo si le pertenece). */
    public function eliminar($id) {
        $uid = $this->requireAuth();
        $result = $this->model->eliminar($id, $uid);
        if ($result) {
            $this->json(['success' => true, 'message' => 'Tarjeta eliminada']);
        } else {
            $this->json(['error' => 'Tarjeta no encontrada'], 404);
        }
    }

    /** Marca una tarjeta del cliente como predeterminada. */
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
