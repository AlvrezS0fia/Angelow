import smtplib
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
from email.mime.base import MIMEBase
from email.mime.image import MIMEImage
from email import encoders
import os
from config import SMTP_CONFIG, APP_URL


def _find_logo_path():
    base = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
    candidates = [
        os.path.join(base, 'app', 'Views', 'emails', 'img', 'logos.png'),
        os.path.join(base, 'app', 'Views', 'emails', 'img', 'logo.png'),
        os.path.join(base, 'public', 'assets', 'imagenes', 'general', 'logos.png'),
        os.path.join(base, 'public', 'assets', 'imagenes', 'general', 'logo.png'),
    ]
    for p in candidates:
        if os.path.exists(p):
            return p
    return None


def enviar_factura_correo(factura, detalles, filepath_pdf=None):
    try:
        msg = MIMEMultipart('mixed')
        msg['From'] = f"{SMTP_CONFIG['from_name']} <{SMTP_CONFIG['from_email']}>"
        msg['To'] = factura['email_cliente']
        msg['Subject'] = f"Factura {factura['numero_factura']} - Angelow"

        logo_path = _find_logo_path()
        if logo_path:
            with open(logo_path, 'rb') as f:
                logo_data = f.read()
            logo_part = MIMEImage(logo_data)
            logo_part.add_header('Content-ID', '<logo_angelow>')
            logo_part.add_header('Content-Disposition', 'inline', filename='logos.png')
            msg.attach(logo_part)

        estado_label = {
            'pendiente': 'Pendiente',
            'autorizada': 'Autorizada - Pagada',
            'cancelada': 'Cancelada',
            'devolucion': 'Devolucion'
        }
        estado_color = {
            'pendiente': '#f59e0b',
            'autorizada': '#10b981',
            'cancelada': '#ef4444',
            'devolucion': '#8b5cf6'
        }

        productos_html = ""
        for det in detalles:
            productos_html += f"""
            <tr>
                <td style="padding:12px 0;border-bottom:1px solid #e8eef6;font-size:14px;color:#1e3a8a;">
                    {det['nombre_producto']}
                    <br><small style="color:#6b8cae;">Talla: {det.get('talla', 'N/A')} | Cant: {det['cantidad']}</small>
                </td>
                <td style="padding:12px 0;border-bottom:1px solid #e8eef6;font-size:14px;color:#4b6a9b;text-align:center;">
                    {det['cantidad']}
                </td>
                <td style="padding:12px 0;border-bottom:1px solid #e8eef6;font-size:14px;color:#4b6a9b;text-align:right;">
                    ${float(det['precio_unitario']):,.0f}
                </td>
                <td style="padding:12px 0;border-bottom:1px solid #e8eef6;font-size:14px;color:#1e3a8a;text-align:right;font-weight:700;">
                    ${float(det['subtotal']):,.0f}
                </td>
            </tr>"""

        descuento_row = ""
        if float(factura.get('descuento', 0)) > 0:
            descuento_row = f"""
            <tr><td style="padding:6px 0;font-size:13px;color:#10b981;" colspan="3">Descuento</td>
            <td style="padding:6px 0;font-size:13px;color:#10b981;text-align:right;">- COP ${float(factura['descuento']):,.0f}</td></tr>"""

        envio_text = "Gratis"
        if float(factura.get('costo_envio', 0)) > 0:
            envio_text = f"COP ${float(factura['costo_envio']):,.0f}"

        fecha_str = factura['fecha_emision'].strftime('%d/%m/%Y') if hasattr(factura['fecha_emision'], 'strftime') else str(factura['fecha_emision'])[:10]

        app_url = APP_URL

        html = f"""<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background-color:#f8f9fa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f8f9fa;padding:32px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 6px 32px rgba(0,0,0,0.08);">

<!-- HEADER -->
<tr><td style="background-color:#ffffff;padding:22px 36px;border-bottom:1px solid #e0e0e0;">
<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
<td style="vertical-align:middle;">
<img src="cid:logo_angelow" alt="Angelow" style="width:48px;height:48px;object-fit:contain;vertical-align:middle;margin-right:12px;">
<span style="font-size:24px;font-weight:700;color:#111111;letter-spacing:-0.4px;vertical-align:middle;">Angelow</span>
</td>
<td align="right" style="vertical-align:middle;">
<span style="background-color:{estado_color.get(factura['estado'], '#f59e0b')}22;color:{estado_color.get(factura['estado'], '#f59e0b')};font-size:11px;font-weight:600;letter-spacing:0.8px;text-transform:uppercase;padding:5px 14px;border-radius:20px;border:1px solid {estado_color.get(factura['estado'], '#f59e0b')}44;">{estado_label.get(factura['estado'], factura['estado'])}</span>
</td>
</tr></table>
</td></tr>

<!-- HERO -->
<tr><td style="background:linear-gradient(150deg,#8eb7db 0%,#adc8eb 50%,#8eb7db 100%);padding:40px;text-align:center;">
<img src="cid:logo_angelow" alt="Angelow" width="52" height="52" style="display:block;margin:0 auto 20px;object-fit:contain;">
<h1 style="margin:0 0 8px;font-size:26px;font-weight:700;color:#111111;">Tu factura de Angelow</h1>
<p style="margin:0 0 20px;font-size:14px;color:#555;">Factura <strong>#{factura['numero_factura']}</strong></p>
<p style="margin:0;font-size:13px;color:#555;">Fecha de emision: {fecha_str}</p>
</td></tr>

<!-- DATOS CLIENTE -->
<tr><td style="padding:32px 40px 0;">
<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
<td width="48%" style="vertical-align:top;">
<p style="margin:0 0 4px;font-size:12px;font-weight:700;color:#7fbbf2;text-transform:uppercase;letter-spacing:1px;">Cliente</p>
<p style="margin:0;font-size:14px;color:#1e3a8a;font-weight:600;">{factura['nombre_cliente']}</p>
<p style="margin:4px 0 0;font-size:13px;color:#4b6a9b;">{factura['email_cliente']}</p>
<p style="margin:4px 0 0;font-size:13px;color:#4b6a9b;">CC: {factura.get('cedula_cliente', 'N/A')}</p>
</td>
<td width="4%"></td>
<td width="48%" style="vertical-align:top;">
<p style="margin:0 0 4px;font-size:12px;font-weight:700;color:#7fbbf2;text-transform:uppercase;letter-spacing:1px;">Direccion de envio</p>
<p style="margin:0;font-size:13px;color:#4b6a9b;">{factura.get('direccion_envio', 'N/A')}</p>
<p style="margin:4px 0 0;font-size:13px;color:#4b6a9b;">{factura.get('ciudad', '')} - {factura.get('departamento', '')}</p>
</td></tr></table>
</td></tr>

<!-- TABLA PRODUCTOS -->
<tr><td style="padding:28px 40px 0;">
<h2 style="margin:0 0 16px;font-size:18px;font-weight:700;color:#111111;">Detalle de compra</h2>
<table width="100%" cellpadding="0" cellspacing="0" border="0">
<thead><tr>
<th style="text-align:left;padding:10px 0;font-size:12px;font-weight:700;color:#7fbbf2;text-transform:uppercase;border-bottom:2px solid #e0e7f5;">Producto</th>
<th style="text-align:center;padding:10px 0;font-size:12px;font-weight:700;color:#7fbbf2;text-transform:uppercase;border-bottom:2px solid #e0e7f5;">Cant.</th>
<th style="text-align:right;padding:10px 0;font-size:12px;font-weight:700;color:#7fbbf2;text-transform:uppercase;border-bottom:2px solid #e0e7f5;">Precio</th>
<th style="text-align:right;padding:10px 0;font-size:12px;font-weight:700;color:#7fbbf2;text-transform:uppercase;border-bottom:2px solid #e0e7f5;">Total</th>
</tr></thead>
<tbody>{productos_html}</tbody>
</table>
</td></tr>

<!-- TOTALES -->
<tr><td style="padding:28px 40px 0;">
<table width="300" cellpadding="0" cellspacing="0" border="0" style="margin-left:auto;">
<tr><td style="padding:8px 0;font-size:14px;color:#4b6a9b;">Subtotal</td>
<td style="padding:8px 0;font-size:14px;color:#4b6a9b;text-align:right;">COP ${float(factura['subtotal']):,.0f}</td></tr>
<tr><td style="padding:8px 0;font-size:14px;color:#4b6a9b;">Envio</td>
<td style="padding:8px 0;font-size:14px;color:#4b6a9b;text-align:right;">{envio_text}</td></tr>
{descuento_row}
<tr><td style="padding:12px 0 0;font-size:18px;font-weight:800;color:#1e3a8a;border-top:2px solid #7fbbf2;">Total</td>
<td style="padding:12px 0 0;font-size:18px;font-weight:800;color:#1e3a8a;border-top:2px solid #7fbbf2;text-align:right;">COP ${float(factura['total']):,.0f}</td></tr>
</table>
</td></tr>

<!-- BOTONES -->
<tr><td style="padding:28px 40px 0;text-align:center;">
<a href="{app_url}/factura/{factura['id']}" style="display:inline-block;background:#5e9de6;color:#ffffff;padding:13px 32px;border-radius:50px;text-decoration:none;font-size:14px;font-weight:600;margin-right:12px;box-shadow:0 4px 12px rgba(94,157,230,0.3);">Ver factura</a>
<a href="{app_url}/api/facturas/{factura['id']}/pdf" style="display:inline-block;background:#ffffff;color:#5e9de6;padding:13px 32px;border-radius:50px;text-decoration:none;font-size:14px;font-weight:600;border:1px solid #5e9de6;">Descargar PDF</a>
</td></tr>

<!-- FOOTER -->
<tr><td style="background-color:#f8f9fa;border-top:1px solid #e0e0e0;padding:28px 40px 24px;">
<p style="margin:0 0 6px;font-size:13px;font-weight:700;color:#111111;">Terminos y condiciones</p>
<p style="margin:0 0 10px;font-size:12px;color:#555555;line-height:1.7;">Esta factura fue generada electronicamente. Para verificar tu factura ingresa a nuestra plataforma.</p>
<p style="margin:0 0 18px;font-size:12px;color:#555555;line-height:1.7;">La informacion personal sera utilizada para la gestion de pedidos y facturacion, conforme a la normativa colombiana de proteccion de datos personales (Ley 1581 de 2012).</p>
<table cellpadding="0" cellspacing="0" border="0" style="width:100%;border-top:1px solid #e0e0e0;">
<tr><td align="left" style="padding:16px 0;">
<a href="{app_url}/documentos/Terminos" style="color:#0060b4;text-decoration:none;font-size:11px;font-weight:600;margin-right:18px;">Terminos de servicio</a>
<a href="{app_url}/documentos/Politicas_Priv" style="color:#0060b4;text-decoration:none;font-size:11px;font-weight:600;margin-right:18px;">Politica de privacidad</a>
<a href="{app_url}/documentos/Politicas_Env" style="color:#0060b4;text-decoration:none;font-size:11px;font-weight:600;">Politica de envios</a>
</td></tr></table>
<p style="margin:16px 0 0;text-align:center;font-size:11px;color:#777777;">Angelow &copy; 2026 — Todos los derechos reservados</p>
</td></tr>

</table>
</td></tr></table>
</body>
</html>"""

        alt_part = MIMEText(html, 'html', 'utf-8')
        msg.attach(alt_part)

        if filepath_pdf and os.path.exists(filepath_pdf):
            with open(filepath_pdf, 'rb') as f:
                part = MIMEBase('application', 'pdf')
                part.set_payload(f.read())
                encoders.encode_base64(part)
                part.add_header(
                    'Content-Disposition',
                    f'attachment; filename="{factura["numero_factura"]}.pdf"'
                )
                msg.attach(part)

        with smtplib.SMTP(SMTP_CONFIG['host'], SMTP_CONFIG['port']) as server:
            server.starttls()
            server.login(SMTP_CONFIG['username'], SMTP_CONFIG['password'])
            server.send_message(msg)

        print(f"Correo enviado a {factura['email_cliente']}")
        return True

    except Exception as e:
        print(f"Error al enviar correo: {e}")
        return False
