<?php
namespace App\Core;

// Rate limiting simple por archivos (sin Redis): cuenta intentos por clave
// (normalmente IP + email) dentro de una ventana móvil. Se guarda en
// storage/logs con nombre ofuscado (md5) para no enumerar emails.
class RateLimiter
{
    private static function dir(): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        return $dir;
    }

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

        if (($now - $data['first']) > $windowSeconds) {
            $data = ['count' => 0, 'first' => $now];
        }
        if ($data['count'] >= $max) {
            return true;
        }
        $data['count']++;
        @file_put_contents($file, json_encode($data), LOCK_EX);
        return false;
    }

    // Limpia el contador al llegar una autenticación exitosa.
    public static function clear(string $key): void
    {
        $file = self::dir() . '/rl_' . md5($key) . '.json';
        if (is_file($file)) {
            @unlink($file);
        }
    }
}