<?php
namespace App\Core;

// CAPA 7 ISO-OSI (Aplicación): autenticación stateless con tokens JWT (HMAC-SHA256).
class JWTHelper
{
    // --- RESOLUCIÓN DEL SECRETO ---
    // Prioridad: $_ENV['JWT_SECRET'] → lectura directa del .env real (ruta
    // correcta app/../../.env o app/../../../.env). NUNCA hay fallback público:
    // si el secreto no existe → null (fail-closed), y encode()/decode() rechazan.
    private static function getSecret(): ?string
    {
        if (!empty($_ENV['JWT_SECRET'])) {
            return (string) $_ENV['JWT_SECRET'];
        }
        foreach ([__DIR__ . '/../../.env', __DIR__ . '/../../../.env'] as $envFile) {
            if (!is_file($envFile)) {
                continue;
            }
            $lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (!$lines) {
                continue;
            }
            foreach ($lines as $line) {
                if (strpos(trim($line), 'JWT_SECRET=') !== 0) {
                    continue;
                }
                $value = trim(substr($line, strlen('JWT_SECRET=')));
                if (($value[0] === '"' && $value[strlen($value) - 1] === '"') ||
                    ($value[0] === "'" && $value[strlen($value) - 1] === "'")) {
                    $value = substr($value, 1, -1);
                }
                if ($value !== '') {
                    return $value;
                }
            }
        }
        return null;
    }

    /** @param string|false $data */
    private static function base64urlEncode($data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /** @param string $data
     * @return string */
    private static function base64urlDecode($data)
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public static function encode(array $payload): ?string
    {
        $secret = self::getSecret();
        if ($secret === null || $secret === '') {
            error_log('[JWT] No hay JWT_SECRET configurado; se rechaza la emisión de tokens.');
            return null;
        }
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $segments = [];
        $segments[] = self::base64urlEncode(json_encode($header));
        $segments[] = self::base64urlEncode(json_encode($payload));
        $signingInput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signingInput, $secret, true);
        $segments[] = self::base64urlEncode($signature);
        return implode('.', $segments);
    }

    public static function decode(string $token): ?array
    {
        $secret = self::getSecret();
        if ($secret === null || $secret === '') {
            error_log('[JWT] No hay JWT_SECRET configurado; se rechazan los tokens.');
            return null;
        }
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $signingInput = "$headerB64.$payloadB64";
        $signature = self::base64urlDecode($signatureB64);
        $expectedSignature = hash_hmac('sha256', $signingInput, $secret, true);

        // CAPA 6 ISO-OSI (Presentación): comparación de firma en tiempo constante (hash_equals).
        if (!hash_equals($expectedSignature, $signature)) return null;

        $payload = json_decode(self::base64urlDecode($payloadB64), true);
        if (!$payload) return null;

        if (isset($payload['exp']) && $payload['exp'] < time()) return null;

        return $payload;
    }
}
