<?php
/**
 * ============================================================
 * ARCHIVO: FavoritoController.php — MÓDULO: API de favoritos
 * ============================================================
 * QUÉ HACE: CRUD de productos favoritos del usuario: listar, agregar
 *   y eliminar. Verifica autenticación antes de cada operación.
 * MODELO(S) QUE USA: Favorito (modelo de favoritos)
 * ENDPOINTS/RUTAS: GET /api/favoritos, POST /api/favoritos/agregar,
 *   DELETE /api/favoritos/eliminar
 * QUIÉN LO CONSUME: favoritos.js (sección de favoritos del perfil cliente)
 */

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\Favorito;


// HERENCIA: controlador API concreto que hereda de la base de controladores.
class FavoritoController extends Controller
{
    private Favorito $favoritoModel;

    /**
     * Obtiene el ID del usuario actual desde sesión (user.id o user_id).
     */
    private function getCurrentUserId()
    {
        return $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;
    }

    /**
     * Verifica si el usuario tiene sesión activa.
     */
    private function isAuthenticated()
    {
        return $this->getCurrentUserId() !== null;
    }

    /**
     * Retorna el email del usuario actual desde sesión.
     */
    private function getCurrentUserEmail()
    {
        return $_SESSION['user']['email'] ?? null;
    }

    public function __construct()
    {
        // Se inyecta el modelo de favoritos para todas las operaciones CRUD.
        $this->favoritoModel = new Favorito();
    }

    /**
     * GET /api/favoritos
     * Obtiene la lista de favoritos del usuario actual (logueado)
     */
    public function index()
    {
        // Verificar que el usuario esté autenticado
        if (!$this->isAuthenticated()) {
            $this->json(['success' => true, 'favoritos' => []]);
            return;
        }

        $usuarioId = $this->getCurrentUserId();
        $favoritos = $this->favoritoModel->getByUsuario($usuarioId);
        $this->json([
            'success' => true,
            'favoritos' => $favoritos,
            'usuario_email' => $this->getCurrentUserEmail()
        ]);
    }

    /**
     * POST /api/favoritos/agregar
     * Agrega un producto a favoritos
     * Body: { producto_id: int }
     */
    public function agregar()
    {
        if (!$this->isAuthenticated()) {
            $this->json(['success' => false, 'message' => 'No autenticado'], 401);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $productoId = $input['producto_id'] ?? 0;

        if (!$productoId) {
            $this->json(['success' => false, 'message' => 'ID de producto requerido'], 400);
            return;
        }

        $usuarioId = $this->getCurrentUserId();

        // Verificar si ya existe
        if ($this->favoritoModel->existe($usuarioId, $productoId)) {
            $this->json(['success' => false, 'message' => 'Ya está en favoritos'], 409);
            return;
        }

        $insertado = $this->favoritoModel->agregar($usuarioId, $productoId);
        if ($insertado) {
            $this->json(['success' => true, 'message' => 'Favorito agregado']);
        } else {
            $this->json(['success' => false, 'message' => 'Error al agregar'], 500);
        }
    }

    /**
     * DELETE /api/favoritos/eliminar
     * Elimina un producto de favoritos
     * Body: { producto_id: int }
     */
    public function eliminar()
    {
        if (!$this->isAuthenticated()) {
            $this->json(['success' => false, 'message' => 'No autenticado'], 401);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $productoId = $input['producto_id'] ?? 0;

        if (!$productoId) {
            $this->json(['success' => false, 'message' => 'ID de producto requerido'], 400);
            return;
        }

        $usuarioId = $this->getCurrentUserId();
        $eliminado = $this->favoritoModel->eliminar($usuarioId, $productoId);
        if ($eliminado) {
            $this->json(['success' => true, 'message' => 'Favorito eliminado']);
        } else {
            $this->json(['success' => false, 'message' => 'No se encontró el favorito'], 404);
        }
    }

    // POLIMORFISMO: sobrescritura (override) de Controller::json() con firma
    // idéntica. La hija reimplementa el contrato heredado; los llamadores usan
    // $this->json(...) sin saber qué implementación se ejecuta.
    /**
     * Envía respuesta JSON
     */
    protected function json(array $data, int $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}