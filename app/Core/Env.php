<?php
/**
 * ============================================================
 * ARCHIVO: Env.php — MÓDULO: Carga de variables de entorno
 * ============================================================
 * QUÉ HACE: Lee el archivo .env (KEY=VALUE) y lo vuelca en $_ENV y
 *           $GLOBALS['env'] para que Database.php y otros módulos las usen.
 *           Se ejecuta una sola vez (guardia ENV_LOADED).
 * ARCHIVO: .env en la raíz del proyecto.
 */
//carga esos datos DEL .env y los pone en $_ENV para que Database.php los use
namespace App\Core;

if (!defined('ENV_LOADED')) {
    define('ENV_LOADED', true);
    $envFile = __DIR__ . '/../../../.env';
    // Initialize $env array in globals if not set
    if (!isset($GLOBALS['env'])) {
        $GLOBALS['env'] = [];
    }
    if (file_exists($envFile)) {
        // Parsea línea por línea el archivo .env (formato KEY=VALUE)
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                // Remove quotes if present
                if (($value[0] === '"' && $value[strlen($value)-1] === '"') ||
                    ($value[0] === "'" && $value[strlen($value)-1] === "'")) {
                    $value = substr($value, 1, -1);
                }
                $_ENV[$key] = $value;
                $GLOBALS['env'][$key] = $value;
            }
        }
    }
}