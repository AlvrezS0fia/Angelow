<?php
namespace App\Core;

/**
 * ============================================================
 * ARCHIVO: RateLimiter.php — MÓDULO: Limitador de peticiones
 * ============================================================
 * QUÉ HACE: Rate limiting simple basado en archivos JSON (sin Redis).
 *           Cuenta intentos por clave (IP + email) dentro de una ventana
 *           móvil de tiempo. Los archivos se guardan en storage/logs/
 *           con nombre ofuscado (md5) para no revelar emails.
 * QUIÉN LO USA: AuthController (login/forgotPassword),
 *               RepartidorAuthController, RepartidorRegistroController
 */
// Rate limiting simple por archivos (sin Redis): cuenta intentos por clave
// (normalmente IP + email) dentro de una ventana móvil. Se guarda en
// storage/logs con nombre ofuscado (md5) para no enumerar emails.
class RateLimiter
{
    /** Retorna el directorio de storage/logs creándolo si no existe. */
    private static function dir(): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        return $dir;
    }

    /** Lee el contador guardado del archivo; devuelve count y first timestamp. */
    /** @return array{count:int,first:int} */
    private static function read(string $file): array
    {
        if (!is_file($file)) {
            return ['count' => 0, 'first' => time()];
        }
        $raw = @file_get_contents($file);
        $decoded = $raw ? json_decode($raw, true) : null;
        if (!is_array($decoded)) {
            return ['count' => 0, 'first' => time()];
        }
        return [
            'count' => (int) ($decoded['count'] ?? 0),
            'first' => (int) ($decoded['first'] ?? time()),
        ];
    }

    // Devuelve true si la clave ya superó el máximo de intentos en la ventana.
    // Si no lo supera, registra un intento más y devuelve false.
    public static function tooMany(string $key, int $max, int $windowSeconds): bool
    {
        $file = self::dir() . '/rl_' . md5($key) . '.json';
        $now = time();
        $data = self::read($file);

        // Ventana móvil: si pasó la ventana desde el primer intento, se reinicia
        if (($now - $data['first']) > $windowSeconds) {
            $data = ['count' => 0, 'first' => $now];
        }
        // Límite alcanzado: se rechaza sin incrementar el contador
        if ($data['count'] >= $max) {
            return true;
        }
        // Intento permitido: se incrementa y persiste con bloqueo de escritura
        $data['count']++;
        @file_put_contents($file, json_encode($data), LOCK_EX);
        return false;
    }

    // Limpia el contador al llegar una autenticación exitosa.
    // (bonifica al usuario legítimo eliminando su registro de intentos).
    public static function clear(string $key): void
    {
        $file = self::dir() . '/rl_' . md5($key) . '.json';
        if (is_file($file)) {
            @unlink($file);
        }
    }
}