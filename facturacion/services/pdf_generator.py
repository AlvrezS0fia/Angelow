import os
from fpdf import FPDF


def _s(val, default=''):
    if val is None:
        return default
    return str(val)


BLUE = (30, 58, 138)
LIGHT_BLUE = (94, 157, 230)
PALE_BLUE = (237, 244, 252)
HEADER_BG = (30, 58, 138)
ROW_ALT = (248, 251, 254)
GRAY_TEXT = (75, 106, 155)
DARK_TEXT = (30, 58, 138)
GREEN = (16, 185, 129)
WHITE = (255, 255, 255)
BORDER_COLOR = (224, 231, 245)


class FacturaPDF(FPDF):
    def __init__(self, factura, detalles):
        super().__init__()
        self.factura = factura
        self.detalles = detalles or []
        self.set_auto_page_break(auto=True, margin=25)

    def _find_logo(self):
        base = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
        candidates = [
            os.path.join(base, 'public', 'assets', 'imagenes', 'general', 'logos.png'),
            os.path.join(base, 'app', 'Views', 'emails', 'img', 'logos.png'),
            os.path.join(base, 'public', 'assets', 'imagenes', 'general', 'logo.png'),
        ]
        for p in candidates:
            if os.path.exists(p):
                return p
        return None

    def header(self):
        logo = self._find_logo()
        if logo:
            try:
                self.image(logo, 15, 10, 20, 20)
            except Exception:
                pass

        self.set_font('Helvetica', 'B', 24)
        self.set_text_color(*BLUE)
        self.set_xy(40, 11)
        self.cell(0, 10, 'ANGELOW')

        self.set_font('Helvetica', '', 10)
        self.set_text_color(*GRAY_TEXT)
        self.set_xy(40, 21)
        self.cell(0, 5, 'Moda Infantil - Calidad y Estilo')

        self.set_font('Helvetica', 'B', 9)
        self.set_text_color(*LIGHT_BLUE)
        self.set_xy(140, 12)
        self.cell(0, 5, 'FACTURA ELECTRONICA', align='R')

        f = self.factura
        estado_map = {'pendiente': 'PENDIENTE', 'autorizada': 'AUTORIZADA', 'cancelada': 'CANCELADA', 'devolucion': 'DEVOLUCION'}
        estado_text = estado_map.get(_s(f.get('estado')), 'PENDIENTE')
        num = _s(f.get('numero_factura'))
        self.set_xy(140, 18)
        self.cell(0, 5, f'{estado_text}  |  {num}', align='R')

        self.set_draw_color(*BLUE)
        self.set_line_width(0.8)
        self.line(15, 30, 195, 30)

    def footer(self):
        self.set_y(-22)
        self.set_draw_color(*BORDER_COLOR)
        self.set_line_width(0.3)
        self.line(15, self.get_y(), 195, self.get_y())
        self.ln(4)
        self.set_font('Helvetica', 'I', 8)
        self.set_text_color(160, 170, 190)
        self.cell(0, 4, 'Angelow 2026 - Factura electronica valida segun Ley 527 de 1999', align='C', ln=True)
        self.cell(0, 4, f'Pagina {self.page_no()}/{{nb}}', align='C')

    def build(self):
        self.alias_nb_pages()
        self.add_page()
        f = self.factura

        y = 36

        self.set_fill_color(*PALE_BLUE)
        self.set_draw_color(*BORDER_COLOR)
        self.set_line_width(0.3)

        box_h = 40
        self.rect(15, y, 58, box_h, 'DF')
        self.rect(77, y, 58, box_h, 'DF')
        self.rect(139, y, 56, box_h, 'DF')

        self.set_xy(19, y + 3)
        self.set_font('Helvetica', 'B', 7)
        self.set_text_color(*LIGHT_BLUE)
        self.cell(50, 4, 'TIENDA')

        self.set_xy(19, y + 9)
        self.set_font('Helvetica', 'B', 13)
        self.set_text_color(*BLUE)
        self.cell(50, 6, 'ANGELOW')

        self.set_font('Helvetica', '', 8)
        self.set_text_color(*GRAY_TEXT)
        self.set_xy(19, y + 17)
        self.cell(50, 4, 'Ropa infantil de calidad')
        self.set_xy(19, y + 22)
        self.cell(50, 4, 'NIT: 901.234.567-8')
        self.set_xy(19, y + 27)
        self.cell(50, 4, 'Calle 123 #45-67, Medellin')
        self.set_xy(19, y + 32)
        self.set_text_color(*LIGHT_BLUE)
        self.cell(50, 4, 'info@angelow.com')

        self.set_xy(81, y + 3)
        self.set_font('Helvetica', 'B', 7)
        self.set_text_color(*LIGHT_BLUE)
        self.cell(50, 4, 'CLIENTE')

        self.set_xy(81, y + 9)
        self.set_font('Helvetica', 'B', 10)
        self.set_text_color(*BLUE)
        nombre = _s(f.get('nombre_cliente'), 'Cliente')
        self.cell(50, 5, nombre[:28])

        self.set_font('Helvetica', '', 8)
        self.set_text_color(*GRAY_TEXT)
        self.set_xy(81, y + 16)
        self.cell(50, 4, f'CC: {_s(f.get("cedula_cliente"), "N/A")}')
        self.set_xy(81, y + 21)
        email = _s(f.get('email_cliente'))
        self.cell(50, 4, email[:28] if email else '')
        self.set_xy(81, y + 26)
        tel = _s(f.get('telefono_cliente'))
        self.cell(50, 4, tel[:28] if tel else '')
        self.set_xy(81, y + 31)
        self.cell(50, 4, f'Metodo: {_s(f.get("metodo_pago"), "N/A")}')

        self.set_xy(143, y + 3)
        self.set_font('Helvetica', 'B', 7)
        self.set_text_color(*LIGHT_BLUE)
        self.cell(48, 4, 'DETALLES DEL PAGO')

        self.set_font('Helvetica', '', 8)
        self.set_text_color(*GRAY_TEXT)
        fecha_str = _s(f.get('fecha_emision'))[:10]
        self.set_xy(143, y + 10)
        self.cell(48, 4, f'Fecha: {fecha_str}')
        self.set_xy(143, y + 16)
        self.cell(48, 4, f'Metodo: {_s(f.get("metodo_pago"), "N/A")}')

        estado_labels = {'pendiente': 'Pendiente', 'autorizada': 'Autorizada', 'cancelada': 'Cancelada', 'devolucion': 'Devolucion'}
        estado_text = estado_labels.get(_s(f.get('estado')), 'Pendiente')
        self.set_xy(143, y + 22)
        self.set_font('Helvetica', 'B', 9)
        self.set_text_color(*GREEN)
        self.cell(48, 5, f'Estado: {estado_text}')

        num_pedido = _s(f.get('numero_pedido'))
        if num_pedido:
            self.set_font('Helvetica', '', 8)
            self.set_text_color(*GRAY_TEXT)
            self.set_xy(143, y + 29)
            self.cell(48, 4, f'Pedido: {num_pedido}')

        y += box_h + 8

        self.set_font('Helvetica', 'B', 14)
        self.set_text_color(*BLUE)
        self.cell(0, 8, 'Detalle de productos', ln=True)
        y = self.get_y() + 2

        self.set_fill_color(*HEADER_BG)
        self.set_font('Helvetica', 'B', 8)
        self.set_text_color(*WHITE)
        col_widths = [72, 20, 18, 28, 32]
        headers = ['PRODUCTO', 'TALLA', 'CANT.', 'PRECIO', 'SUBTOTAL']
        for i, h in enumerate(headers):
            align = 'R' if i >= 3 else ('C' if i in (1, 2) else 'L')
            self.cell(col_widths[i], 8, f'  {h}' if i == 0 else h, border=0, align=align, fill=True)
        self.ln()

        subtotal_calc = 0
        self.set_font('Helvetica', '', 9)
        for idx, det in enumerate(self.detalles):
            total_det = float(_s(det.get('subtotal'), '0')) or (float(_s(det.get('precio_unitario'), '0')) * int(_s(det.get('cantidad'), '0')))
            subtotal_calc += total_det

            if idx % 2 == 0:
                self.set_fill_color(*ROW_ALT)
            else:
                self.set_fill_color(*WHITE)

            self.set_text_color(*DARK_TEXT)
            nombre_p = _s(det.get('nombre_producto'))[:32]
            talla = _s(det.get('talla'), 'N/A')
            cant = _s(det.get('cantidad'), '0')
            precio = f'$ {float(_s(det.get("precio_unitario"), "0")):,.0f}'
            sub = f'$ {total_det:,.0f}'

            self.cell(col_widths[0], 7, f'  {nombre_p}', border=0, fill=True)
            self.cell(col_widths[1], 7, talla, border=0, align='C', fill=True)
            self.cell(col_widths[2], 7, cant, border=0, align='C', fill=True)
            self.cell(col_widths[3], 7, precio, border=0, align='R', fill=True)
            self.set_font('Helvetica', 'B', 9)
            self.cell(col_widths[4], 7, sub, border=0, align='R', fill=True)
            self.set_font('Helvetica', '', 9)
            self.ln()

        self.set_draw_color(*LIGHT_BLUE)
        self.set_line_width(0.5)
        self.line(15, self.get_y() + 1, 195, self.get_y() + 1)
        self.ln(6)

        descuento = float(_s(f.get('descuento'), '0'))
        costo_envio = float(_s(f.get('costo_envio'), '0'))
        total = float(_s(f.get('total'), str(subtotal_calc)))

        totals_x = 110
        totals_w = 80

        self.set_font('Helvetica', '', 10)
        self.set_text_color(*GRAY_TEXT)

        self.set_x(totals_x)
        self.cell(totals_w - 35, 7, 'Subtotal', border='B')
        self.cell(35, 7, f'$ {subtotal_calc:,.0f}', border='B', align='R', ln=True)

        envio_text = 'Gratis' if costo_envio == 0 else f'$ {costo_envio:,.0f}'
        self.set_x(totals_x)
        self.cell(totals_w - 35, 7, 'Envio', border='B')
        self.cell(35, 7, envio_text, border='B', align='R', ln=True)

        if descuento > 0:
            self.set_text_color(*GREEN)
            self.set_x(totals_x)
            self.cell(totals_w - 35, 7, 'Descuento', border='B')
            self.cell(35, 7, f'- $ {descuento:,.0f}', border='B', align='R', ln=True)

        self.set_draw_color(*BLUE)
        self.set_line_width(0.8)
        self.set_font('Helvetica', 'B', 13)
        self.set_text_color(*BLUE)
        self.set_x(totals_x)
        self.cell(totals_w - 35, 10, 'TOTAL')
        self.cell(35, 10, f'$ {total:,.0f}', align='R', ln=True)

        self.ln(6)

        box_y = self.get_y()
        self.set_fill_color(*PALE_BLUE)
        self.set_draw_color(*BORDER_COLOR)
        self.set_line_width(0.3)
        self.rect(15, box_y, 180, 22, 'DF')

        self.set_xy(19, box_y + 3)
        self.set_font('Helvetica', 'B', 9)
        self.set_text_color(*BLUE)
        self.cell(0, 5, 'Direccion de envio', ln=True)

        self.set_xy(19, box_y + 9)
        self.set_font('Helvetica', '', 9)
        self.set_text_color(*GRAY_TEXT)
        direccion = _s(f.get('direccion_envio'), 'N/A')
        ciudad = _s(f.get('ciudad'))
        depto = _s(f.get('departamento'))
        dest = _s(f.get('destinatario'))
        addr_text = f'{direccion} - {ciudad}, {depto}'
        self.cell(100, 5, addr_text[:55])
        if dest:
            self.set_xy(120, box_y + 9)
            self.cell(70, 5, f'Dest: {dest[:30]}')

        metodo = _s(f.get('metodo_envio'), 'normal')
        self.set_xy(19, box_y + 15)
        metodo_text = 'Envio Express (1 dia habil)' if metodo == 'express' else 'Envio Normal (2-5 dias habiles)'
        self.cell(100, 5, metodo_text)

        self.ln(8)
        self.ln(3)

        self.set_font('Helvetica', 'I', 7)
        self.set_text_color(160, 170, 190)
        self.cell(0, 4, 'Esta factura se asimila a una factura electronica segun la Ley 527 de 1999.', align='C', ln=True)
        self.cell(0, 4, 'Para verificar su validez ingrese a factura.angelow.com/validar', align='C')


def generar_pdf_archivo(factura, detalles):
    pdf = FacturaPDF(factura, detalles)
    pdf.build()

    output_dir = os.path.join(os.path.dirname(__file__), '..', 'pdfs')
    os.makedirs(output_dir, exist_ok=True)

    pdf_path = os.path.join(output_dir, f"{factura['numero_factura']}.pdf")
    pdf.output(pdf_path)
    return pdf_path
