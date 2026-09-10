<?php
namespace App\Core;

/**
 * ============================================================
 * ARCHIVO: Auth.php — MÓDULO: Autenticación por sesión PHP
 * ============================================================
 * QUÉ HACE: Gestiona el estado de autenticación del usuario actual
 *           a través de $_SESSION. Ofrece verificadores de rol y estado,
 *           y la ruta de inicio según el rol (homeForRole).
 * SESIÓN: $_SESSION['user'] escrita por AuthController::login.
 * QUIÉN LO USA: Todos los controladores para verificar permisos.
 */
class Auth
{
    // --- OBTENER USUARIO ACTUAL ---
    // Devuelve el array completo {id, email, nombre, rol, estado} guardado en la sesión.
    // Entrada: $_SESSION['user'] (escrito por AuthController::login).
    // Salida: array|null → se consume en cualquier controlador/vista.
    public static function user()
    {
        return $_SESSION['user'] ?? null;
    }

    // --- OBTENER ID DEL USUARIO LOGUEADO ---
    // Se usa como valor de seguridad en consultas tipo "WHERE usuario_id = ?"
    // (p.ej. app/Controllers/Cliente/PedidosController.php) para evitar que un
    // cliente lea pedidos ajenos (control de IDOR).
    public static function id()
    {
        return isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null;
    }

    // --- ¿HAY SESIÓN ACTIVA? ---
    // Verdadero si el usuario tiene 'id' en sesión. No distingue roles:
    // sirve de "pasa primero" antes de preguntar por el rol.
    public static function check()
    {
        return !empty($_SESSION['user']['id']);
    }

    // --- OBTENER ROL ACTUAL ---
    // Rol presente en la sesión: 'cliente' | 'repartidor' | 'administrador'.
    // Es la fuente de verdad para TODAS las decisiones de permisos del sistema web.
    public static function rol()
    {
        return $_SESSION['user']['rol'] ?? null;
    }

    // --- OBTENER ESTADO DE LA CUENTA ---
    // Estados posibles: 'pendiente' | 'activo' | 'inactivo' | 'suspendido'.
    // Usado para bloquear cuentas: un repartidor 'pendiente' NO puede operar.
    public static function estado()
    {
        return $_SESSION['user']['estado'] ?? null;
    }

    // --- VERIFICADORES POR ROL ---
    //   if (!Auth::isAdmin()) → devolver 403 o redirigir.
    public static function isCliente()
    {
        return self::rol() === 'cliente';
    }

    public static function isRepartidor()
    {
        return self::rol() === 'repartidor';
    }

    public static function isAdmin()
    {
        return self::rol() === 'administrador';
    }

    // Variante estricta para el panel de repartidor: exige rol repartidor
    // Y estado 'activo' (aprobado por el administrador).
    public static function isRepartidorAprobado()
    {
        return self::isRepartidor() && self::estado() === 'activo';
    }

    public static function homeForRole($rol = null, $estado = null)
    {
        $rol = $rol ?? self::rol();
        $estado = $estado ?? self::estado();
        switch ($rol) {
            case 'administrador':
                return '/admin';
            case 'repartidor':
                if ($estado === 'pendiente') {
                    // Los repartidores pendientes entran al dashboard pero con
                    // bloqueo de acciones: la vista muestra "solicitud en revisión".
                    return '/repartidor/dashboard';
                }
                return '/repartidor/dashboard';
            default:
                return '/';
        }
    }
}