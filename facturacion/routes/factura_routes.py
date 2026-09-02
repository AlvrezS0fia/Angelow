from flask import Blueprint, jsonify, request, send_file
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(__file__)))

from models.factura_model import FacturaModel
from services.pdf_generator import generar_pdf_archivo
from services.email_service import enviar_factura_correo

factura_bp = Blueprint('facturas', __name__)


def require_api_key(f):
    """Guarda las llamadas de servicio a servicio: exige un Bearer token igual a
    FACTURA_API_SECRET (definido solo en .env). Fail-closed si no hay secreto."""
    from functools import wraps

    @wraps(f)
    def decorated(*args, **kwargs):
        from config import FACTURA_API_SECRET
        expected = FACTURA_API_SECRET
        if not expected:
            return jsonify({'error': 'Servicio no configurado (falta FACTURA_API_SECRET)'}), 500
        auth = request.headers.get('Authorization', '')
        token = auth[7:] if auth.startswith('Bearer ') else request.args.get('api_key', '')
        if token != expected:
            return jsonify({'error': 'No autorizado'}), 401
        return f(*args, **kwargs)
    return decorated


@factura_bp.route('/api/facturas', methods=['GET'])
@require_api_key
def listar_facturas():
    try:
        estado = request.args.get('estado', 'all')
        model = FacturaModel()
        facturas = model.get_all(estado)
        model._close()
        for f in facturas:
            for key, value in f.items():
                if hasattr(value, 'strftime'):
                    f[key] = str(value)
        return jsonify(facturas)
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@factura_bp.route('/api/facturas/stats', methods=['GET'])
@require_api_key
def estadisticas():
    try:
        model = FacturaModel()
        stats = model.get_stats()
        model._close()
        return jsonify(stats)
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@factura_bp.route('/api/facturas/<int:factura_id>', methods=['GET'])
@require_api_key
def obtener_factura(factura_id):
    try:
        model = FacturaModel()
        factura = model.get_by_id(factura_id)
        model._close()
        if not factura:
            return jsonify({'error': 'Factura no encontrada'}), 404

        for key, value in factura.items():
            if hasattr(value, 'strftime'):
                factura[key] = str(value)

        for d in factura.get('detalles', []):
            for k, v in d.items():
                if hasattr(v, 'strftime'):
                    d[k] = str(v)

        for h in factura.get('historial', []):
            for k, v in h.items():
                if hasattr(v, 'strftime'):
                    h[k] = str(v)

        return jsonify(factura)
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@factura_bp.route('/api/facturas/pedido/<int:pedido_id>', methods=['GET'])
@require_api_key
def factura_por_pedido(pedido_id):
    try:
        model = FacturaModel()
        factura = model.get_by_pedido(pedido_id)
        model._close()
        if not factura:
            return jsonify({'error': 'No existe factura para este pedido'}), 404

        for key, value in factura.items():
            if hasattr(value, 'strftime'):
                factura[key] = str(value)
        for d in factura.get('detalles', []):
            for k, v in d.items():
                if hasattr(v, 'strftime'):
                    d[k] = str(v)

        return jsonify(factura)
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@factura_bp.route('/api/facturas/crear', methods=['POST'])
@require_api_key
def crear_factura():
    try:
        data = request.get_json()
        pedido_id = data.get('pedido_id')
        admin_id = data.get('admin_id')

        if not pedido_id:
            return jsonify({'error': 'pedido_id es requerido'}), 400

        model = FacturaModel()
        factura, error = model.crear_desde_pedido(pedido_id, admin_id)
        model._close()

        if error:
            if factura and factura.get('id'):
                for key, value in factura.items():
                    if hasattr(value, 'strftime'):
                        factura[key] = str(value)
                return jsonify({'success': True, 'factura': factura, 'message': error})
            return jsonify({'error': error}), 400

        for key, value in factura.items():
            if hasattr(value, 'strftime'):
                factura[key] = str(value)

        return jsonify({'success': True, 'factura': factura})
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@factura_bp.route('/api/facturas/<int:factura_id>/estado', methods=['PUT'])
@require_api_key
def cambiar_estado(factura_id):
    try:
        data = request.get_json()
        nuevo_estado = data.get('estado')
        admin_id = data.get('admin_id')
        notas = data.get('notas')

        if nuevo_estado not in ('pendiente', 'autorizada', 'cancelada', 'devolucion'):
            return jsonify({'error': 'Estado invalido'}), 400

        model = FacturaModel()
        factura, error = model.cambiar_estado(
            factura_id, nuevo_estado, admin_id, notas)
        model._close()

        if error:
            return jsonify({'error': error}), 400

        for key, value in factura.items():
            if hasattr(value, 'strftime'):
                factura[key] = str(value)

        return jsonify({'success': True, 'factura': factura})
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@factura_bp.route('/api/facturas/<int:factura_id>/enviar-correo', methods=['POST'])
@require_api_key
def enviar_correo(factura_id):
    try:
        model = FacturaModel()
        factura = model.get_by_id(factura_id)
        if not factura:
            model._close()
            return jsonify({'error': 'Factura no encontrada'}), 404

        detalles = factura.get('detalles', [])
        pdf_path = generar_pdf_archivo(factura, detalles)

        enviado = enviar_factura_correo(factura, detalles, pdf_path)

        if enviado:
            model.marcar_enviado_correo(factura_id)
            model._close()
            return jsonify({'success': True, 'message': 'Correo enviado exitosamente'})
        else:
            model._close()
            return jsonify({'error': 'Error al enviar el correo'}), 500
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@factura_bp.route('/api/facturas/<int:factura_id>/pdf', methods=['GET'])
@require_api_key
def descargar_pdf(factura_id):
    try:
        model = FacturaModel()
        factura = model.get_by_id(factura_id)
        model._close()

        if not factura:
            return jsonify({'error': 'Factura no encontrada'}), 404

        detalles = factura.get('detalles', [])
        pdf_path = generar_pdf_archivo(factura, detalles)

        return send_file(pdf_path, mimetype='application/pdf',
                         as_attachment=True,
                         download_name=f"{factura['numero_factura']}.pdf")
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@factura_bp.route('/api/facturas/<int:factura_id>/pdf-inline', methods=['GET'])
@require_api_key
def ver_pdf_inline(factura_id):
    try:
        model = FacturaModel()
        factura = model.get_by_id(factura_id)
        model._close()

        if not factura:
            return jsonify({'error': 'Factura no encontrada'}), 404

        detalles = factura.get('detalles', [])
        pdf_path = generar_pdf_archivo(factura, detalles)

        return send_file(pdf_path, mimetype='application/pdf',
                         as_attachment=False)
    except Exception as e:
        return jsonify({'error': str(e)}), 500
