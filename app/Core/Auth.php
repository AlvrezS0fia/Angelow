<?php
namespace App\Core;

class Auth
{
    public static function user()
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id()
    {
        return isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null;
    }

    public static function check()
    {
        return !empty($_SESSION['user']['id']);
    }

    public static function rol()
    {
        return $_SESSION['user']['rol'] ?? null;
    }

    public static function estado()
    {
        return $_SESSION['user']['estado'] ?? null;
    }

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

    public static function isRepartidorAprobado()
    {
        return self::isRepartidor() && self::estado() === 'activo';
    }

    // Redirección según el rol del usuario autenticado.
    // Devuelve la URL del panel correspondiente.
    public static function homeForRole($rol = null, $estado = null)
    {
        $rol = $rol ?? self::rol();
        $estado = $estado ?? self::estado();
        switch ($rol) {
            case 'administrador':
                return '/admin';
            case 'repartidor':
                if ($estado === 'pendiente') {
                    return '/repartidor/dashboard';
                }
                return '/repartidor/dashboard';
            default:
                return '/';
        }
    }
}
