 function generarNumeroFactura() {
    const anio = new Date().getFullYear();
    const aleatorio = Math.floor(Math.random() * 9000 + 1000);
    return `A-${anio}-${aleatorio}`;
  }

  function cargarDatosFactura() {
    let compraData = null;
    try {
      const storedOrder = localStorage.getItem('ultimaCompra');
      if (storedOrder) {
        compraData = JSON.parse(storedOrder);
      }
    } catch(e) { console.warn(e); }

    if (compraData && compraData.productos && compraData.productos.length > 0) {
      document.getElementById('clienteNombre').innerText = compraData.cliente?.nombre || 'Cliente ANGELOW';
      document.getElementById('clienteCedula').innerText = compraData.cliente?.cedula || 'CC ********';
      document.getElementById('clienteEmail').innerText = compraData.cliente?.email || 'cliente@angelow.com';
      document.getElementById('clienteTelefono').innerText = compraData.cliente?.telefono || '---';
      document.getElementById('direccionEnvio').innerText = compraData.envio?.direccion || 'Calle principal #123';
      document.getElementById('destinatarioEnvio').innerHTML = `<strong>Destinatario:</strong> ${compraData.envio?.destinatario || compraData.cliente?.nombre || 'Cliente'}`;
      document.getElementById('metodoEnvio').innerText = compraData.metodo_envio === 'express' ? 'Envío Express · 1 día hábil' : 'Envío Normal · 2-5 días hábiles';
      document.getElementById('metodoPago').innerText = compraData.metodo_pago === 'mercadopago' ? 'Mercado Pago · **** 4242' : 'Botón de pago / PSE';
      
      let subtotal = 0;
      const tbody = document.getElementById('invoiceItemsBody');
      tbody.innerHTML = '';
      compraData.productos.forEach(prod => {
        const totalProducto = prod.precio * prod.cantidad;
        subtotal += totalProducto;
        const row = `<td>
          <td><div class="product-cell"><div class="product-img"><img src="${prod.imagen || APP_URL + '/assets/imagenes/general/producto.png'}" onerror="this.src=APP_URL + '/assets/imagenes/general/producto.png'"></div><div class="product-info"><h4>${prod.nombre}</h4><span>Ref: ${prod.referencia || 'GEN'}</span></div></div></td>
          <td>${prod.talla || 'Única'}</td>
          <td>${prod.cantidad}</td>
          <td>COP $${prod.precio.toLocaleString()}</td>
          <td><strong>COP $${totalProducto.toLocaleString()}</strong></td>
        </tr>`;
        tbody.insertAdjacentHTML('beforeend', row);
      });
      
      let descuento = compraData.descuento || 0;
      let total = compraData.total || subtotal;
      let envio = compraData.metodo_envio === 'express' ? 15000 : 0;
      
      document.getElementById('subtotalFactura').innerText = `COP $${subtotal.toLocaleString()}`;
      document.getElementById('envioFactura').innerText = envio === 0 ? 'Gratis' : `COP $${envio.toLocaleString()}`;
      if (descuento > 0) {
        document.getElementById('descuentoRow').style.display = 'flex';
        document.getElementById('codigoDescuento').innerText = compraData.codigo_descuento || 'DESCUENTO';
        document.getElementById('descuentoMonto').innerText = `- COP $${descuento.toLocaleString()}`;
      } else {
        document.getElementById('descuentoRow').style.display = 'none';
      }
      document.getElementById('totalFactura').innerText = `COP $${total.toLocaleString()}`;
      
    } else {
      const productosDemo = [
        { nombre: "Vestido Floral Primavera", talla: "8-9 años", cant: 2, precio: 89900, ref: "VF-245", imagen: APP_URL + "/assets/imagenes/ninas/Frente Conjunto Infantil.png" },
        { nombre: "Conjunto Deportivo Niño", talla: "6-7 años", cant: 1, precio: 129900, ref: "CD-128", imagen: APP_URL + "/assets/imagenes/ninos/Frente Conjunto Deportivo.png" },
        { nombre: "Zapatos Escolares", talla: "28", cant: 1, precio: 149900, ref: "ZE-342", imagen: APP_URL + "/assets/imagenes/general/producto.png" }
      ];
      let subtotalDemo = 0;
      const tbody = document.getElementById('invoiceItemsBody');
      tbody.innerHTML = '';
      productosDemo.forEach(prod => {
        const totalProd = prod.precio * prod.cant;
        subtotalDemo += totalProd;
        const row = `<tr>
          <td><div class="product-cell"><div class="product-img"><img src="${prod.imagen}" onerror="this.src=APP_URL + '/assets/imagenes/general/producto.png'"></div><div class="product-info"><h4>${prod.nombre}</h4><span>Ref: ${prod.ref}</span></div></div></td>
          <td>${prod.talla}</td>
          <td>${prod.cant}</td>
          <td>COP $${prod.precio.toLocaleString()}</td>
          <td><strong>COP $${totalProd.toLocaleString()}</strong></td>
        </tr>`;
        tbody.insertAdjacentHTML('beforeend', row);
      });
      const descuentoDemo = 42960;
      const totalDemo = 386640;
      document.getElementById('subtotalFactura').innerText = `COP $${subtotalDemo.toLocaleString()}`;
      document.getElementById('envioFactura').innerText = 'Gratis';
      document.getElementById('descuentoRow').style.display = 'flex';
      document.getElementById('codigoDescuento').innerText = 'BIENVENIDA10';
      document.getElementById('descuentoMonto').innerText = `- COP $${descuentoDemo.toLocaleString()}`;
      document.getElementById('totalFactura').innerText = `COP $${totalDemo.toLocaleString()}`;
      document.getElementById('clienteNombre').innerText = 'María José González';
      document.getElementById('clienteCedula').innerText = '1.234.567.890';
      document.getElementById('clienteEmail').innerText = 'maria.g@angelow.com';
      document.getElementById('clienteTelefono').innerText = '300 123 4567';
      document.getElementById('direccionEnvio').innerText = 'Carrera 48 # 20-35, Apto 302, Laureles, Medellín';
      document.getElementById('destinatarioEnvio').innerHTML = '<strong>Destinatario:</strong> María González';
      document.getElementById('metodoEnvio').innerText = 'Envío Express · 1 día hábil';
      document.getElementById('metodoPago').innerText = 'Mercado Pago · **** 4242';
    }
  }

  function mostrarFecha() {
    const opciones = { year: 'numeric', month: 'long', day: 'numeric' };
    const fecha = new Date().toLocaleDateString('es-ES', opciones);
    document.getElementById('fechaFactura').innerText = fecha;
  }
  
  function actualizarNumeroFactura() {
    const numero = generarNumeroFactura();
    const badge = document.getElementById('invoiceBadge');
    if (badge) badge.textContent = `PAGADA · FACTURA #${numero}`;
  }

  document.addEventListener('DOMContentLoaded', () => {
    cargarDatosFactura();
    mostrarFecha();
    actualizarNumeroFactura();
  });