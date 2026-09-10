<?php
/**
 * ============================================================
 * ARCHIVO: DocumentoController.php — MÓDULO: Controlador de documentos legales/informativos
 * ============================================================
 * QUÉ HACE: Sirve las páginas informativas y legales de la tienda:
 *   pedidos y envíos, políticas de devolución, Preguntas frecuentes,
 *   guía de tallas, términos y condiciones, políticas de privacidad y de envío.
 * MODELO(S) QUE USA: ninguno
 * ENDPOINTS/RUTAS: GET /documentos/pedidos-envios, /politicas-devolucion,
 *   /preguntas, /guia-tallas, /terminos, /politicas-privacidad, /politicas-envio
 * QUIÉN LO CONSUME: Enlaces del footer de la tienda (vistas públicas).
 */
namespace App\Controllers;

use App\Core\Controller;

/**
 * Controlador de páginas documentales/legales. Extiende la clase base Controller.
 * Patrón MVC: cada método público renderiza una vista estática de documentos.
 */
class DocumentoController extends Controller
{
    /** Muestra la página "Pedidos y envíos". */
    public function pedidosEnvios()
    {
        $this->view('documentos.Pedidos_envios');
    }

    /** Muestra la página "Políticas de devolución". */
    public function politicasDevolucion()
    {
        $this->view('documentos.Politicas_devolucion');
    }

    /** Muestra la página de "Preguntas frecuentes". */
    public function preguntas()
    {
        $this->view('documentos.Preguntas');
    }

    /** Muestra la "Guía de tallas". */
    public function guiaTallas()
    {
        $this->view('documentos.Guia_Tallas');
    }

    /** Muestra los "Términos y Condiciones". */
    public function terminos()
    {
        $this->view('documentos.Terminos');
    }

    /** Muestra la "Política de privacidad". */
    public function politicasPrivacidad()
    {
        $this->view('documentos.Politicas_Priv');
    }

    /** Muestra la "Política de envíos". */
    public function politicasEnv()
    {
        $this->view('documentos.Politicas_Env');
    }
}