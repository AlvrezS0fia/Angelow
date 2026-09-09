<?php
namespace App\Controllers\Api;

use App\Core\Database;

class CuponController {

    public function validar() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        $codigo = strtoupper(trim($data['codigo'] ?? ''));
        $subtotal = (float)($data['subtotal'] ?? 0);

        if (!$codigo) {
            echo json_encode(['success' => false, 'message' => 'Código no proporcionado']);
            return;
        }

        $cupon = Database::query(
            "SELECT * FROM cupones WHERE codigo = ? AND activo = TRUE",
            [$codigo]
        )->fetch();

        if (!$cupon) {
            echo json_encode(['success' => false, 'message' => 'Cupón inválido o inactivo']);
            return;
        }

        // Verificar vigencia
        if ($cupon['fecha_inicio'] && date('Y-m-d') < $cupon['fecha_inicio']) {
            echo json_encode(['success' => false, 'message' => 'El cupón aún no está vigente']);
            return;
        }
        if ($cupon['fecha_fin'] && date('Y-m-d') > $cupon['fecha_fin']) {
            echo json_encode(['success' => false, 'message' => 'El cupón ha expirado']);
            return;
        }

        // Verificar usos máximos
        if ($cupon['usos_maximos'] !== null && $cupon['usos_actuales'] >= $cupon['usos_maximos']) {
            echo json_encode(['success' => false, 'message' => 'El cupón ha alcanzado el máximo de usos']);
            return;
        }

        // Verificar monto mínimo
        if ($subtotal < (float)$cupon['valor_minimo_compra']) {
            echo json_encode([
                'success' => false,
                'message' => 'El subtotal mínimo para este cupón es $' . number_format($cupon['valor_minimo_compra'], 0, ',', '.')
            ]);
            return;
        }

        // Calcular descuento
        $descuento = 0;
        switch ($cupon['tipo']) {
            case 'porcentaje':
                $descuento = $subtotal * ((float)$cupon['valor'] / 100);
                break;
            case 'monto_fijo':
                $descuento = min((float)$cupon['valor'], $subtotal);
                break;
            case 'envio_gratis':
                $descuento = 0;
                break;
        }

        echo json_encode([
            'success' => true,
            'descuento' => round($descuento, 2),
            'tipo' => $cupon['tipo'],
            'valor' => (float)$cupon['valor'],
            'descripcion' => $cupon['descripcion'] ?? $cupon['codigo']
        ]);
    }
}
