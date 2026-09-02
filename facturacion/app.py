import os
import sys

sys.path.insert(0, os.path.dirname(__file__))

from flask import Flask
from flask_cors import CORS
from config import PYTHON_PORT
from routes.factura_routes import factura_bp


def create_app():
    app = Flask(__name__)
    app.config['JSON_AS_ASCII'] = False

    CORS(app, resources={r"/api/*": {"origins": "*"}})

    app.register_blueprint(factura_bp)

    @app.route('/api/health', methods=['GET'])
    def health():
        return {'status': 'ok', 'service': 'facturacion'}

    return app


if __name__ == '__main__':
    app = create_app()
    print(f"Servicio de Facturacion iniciado en http://localhost:{PYTHON_PORT}")
    app.run(host='0.0.0.0', port=PYTHON_PORT, debug=True)
