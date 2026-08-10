<?php
namespace App\Core;

class JWTHelper
{
    private static function getSecret()
    {
        return $_ENV['JWT_SECRET'] ?? 'angelow_jwt_secret_key_2026';
    }

    private static function base64urlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64urlDecode($data)
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public static function encode($payload)
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $segments = [];
        $segments[] = self::base64urlEncode(json_encode($header));
        $segments[] = self::base64urlEncode(json_encode($payload));
        $signingInput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signingInput, self::getSecret(), true);
        $segments[] = self::base64urlEncode($signature);
        return implode('.', $segments);
    }

    public static function decode($token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $signingInput = "$headerB64.$payloadB64";
        $signature = self::base64urlDecode($signatureB64);
        $expectedSignature = hash_hmac('sha256', $signingInput, self::getSecret(), true);

        if (!hash_equals($expectedSignature, $signature)) return null;

        $payload = json_decode(self::base64urlDecode($payloadB64), true);
        if (!$payload) return null;

        if (isset($payload['exp']) && $payload['exp'] < time()) return null;

        return $payload;
    }
}
