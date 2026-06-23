<?php
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\Favorito;

class FavoritoController extends Controller
{
    private $favoritoModel;

    private function getCurrentUserId()
    {
        return $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;
    }

    private function isAuthenticated()
    {
        return $this->getCurrentUserId() !== null;
    }

    private function getCurrentUserEmail()
    {
        return $_SESSION['user']['email'] ?? null;
    }

    public function __construct()
    {
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

    /**
     * Envía respuesta JSON
     */
    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}