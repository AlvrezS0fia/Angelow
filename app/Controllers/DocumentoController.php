<?php
namespace App\Controllers;

use App\Core\Controller;

class DocumentoController extends Controller
{
    public function pedidosEnvios()
    {
        $this->view('documentos.Pedidos_envios');
    }

    public function politicasDevolucion()
    {
        $this->view('documentos.Politicas_devolucion');
    }

    public function preguntas()
    {
        $this->view('documentos.Preguntas');
    }

    public function guiaTallas()
    {
        $this->view('documentos.Guia_Tallas');
    }

    public function terminos()
    {
        $this->view('documentos.Terminos');
    }

    public function politicasPrivacidad()
    {
        $this->view('documentos.Politicas_Priv');
    }

    public function politicasEnv()
    {
        $this->view('documentos.Politicas_Env');
    }
}