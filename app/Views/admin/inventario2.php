<?php
/*
 |========================================================================
 | VISTA: admin/inventario2.php
 |------------------------------------------------------------------------
 | QUÉ MUESTRA: Gestión de inventario CONECTADA AL MICROSERVICIO
 | (Spring Boot + JPA, puerto 8082, endpoint /api/inventario).
 |
 | EL PHP NO TOCA MySQL: el listado, la PAGINACIÓN REAL (RETO 5), las
 | búsquedas AND (2 campos, RETO 1) y OR (3 campos, RETO 1), el CRUD y
 | las validaciones (@Valid, RETO 4) las resuelve el microservicio.
 |
 | El controlador (Inventario2Controller) pasa:
 |   $productosIniciales  -> primeras filas (por si el microservicio no
 |                           responde, para mostrar un aviso)
 |   $errorMicroservicio  -> mensaje si el microservicio está caído
 |   $productosApiUrl     -> URL base centralizada (PRODUCTOS_API_URL)
 |========================================================================
*/
if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
    header('Location: ' . APP_URL . '/auth/login');
    exit();
}
$apiUrl = rtrim($productosApiUrl ?? '', '/');
$conExito = empty($errorMicroservicio);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario (Microservicio) - ANGELOW</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/back-button.css">
    <script>const API_URL = <?= json_encode($apiUrl) ?>;</script>
    <style>
        :root {
            --primary: #5E9DE6; --primary-hover: #3B82F6; --bg-light: #F8FBFE; --bg-soft: #EDF4FC;
            --text-dark: #1E3A8A; --text-secondary: #4B6A9B; --border-light: #E0E7F5;
            --success: #10b981; --warning: #f59e0b; --error: #ef4444;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-light); color: var(--text-dark); min-height: 100vh; }
        .angelow-header {
            background: #fff; border-bottom: 1px solid var(--border-light); padding: 16px 30px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 15px rgba(94,157,230,.12); position: sticky; top: 0; z-index: 100;
        }
        .header-left { display: flex; align-items: center; gap: 16px; }
        .estado { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-secondary); }
        .punto { width: 11px; height: 11px; border-radius: 50%; background: #ccc; }
        .punto-ok { background: var(--success); }
        .punto-error { background: var(--error); }
        main { max-width: 1300px; margin: 30px auto; padding: 0 24px; }
        .section-title { font-size: 26px; margin-bottom: 20px; }
        .aviso { background: #FEF3C7; color: #92400E; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; border: 1px solid #FDE68A; }

        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 16px; margin-bottom: 22px; }
        .stat { background: #fff; border-radius: 16px; padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,.05); }
        .stat .num { font-size: 28px; font-weight: 800; }
        .stat .txt { font-size: 13px; color: var(--text-secondary); }

        .barra { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; background: #fff; padding: 16px; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,.05); margin-bottom: 22px; }
        input[type="text"], select { padding: 9px 14px; border: 1px solid var(--border-light); border-radius: 10px; font-size: 14px; outline: none; }
        input[type="text"]:focus, select:focus { border-color: var(--primary); }
        .btn { padding: 9px 18px; border: none; border-radius: 24px; cursor: pointer; font-weight: 600; font-size: 14px; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-soft { background: #EDF2F7; color: var(--text-dark); }
        .btn-soft:hover { background: #dfe6ef; }
        .btn-new { background: var(--success); color: #fff; }
        .btn-danger { background: var(--error); color: #fff; }

        .card { background: #fff; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,.05); overflow: hidden; margin-bottom: 22px; }
        .card-head { padding: 16px 22px; border-bottom: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center; }
        .resultado { font-size: 13px; color: var(--text-secondary); }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 13px 16px; font-size: 12px; text-transform: uppercase; color: var(--text-secondary); background: #F8FAFC; border-bottom: 1px solid var(--border-light); }
        td { padding: 13px 16px; border-bottom: 1px solid var(--border-light); }
        tbody tr:hover { background: #F8FAFC; }
        .precio { font-weight: 700; }
        .cat { background: var(--bg-soft); padding: 4px 10px; border-radius: 20px; font-size: 12px; }
        .badge { padding: 4px 12px; border-radius: 30px; font-size: 12px; font-weight: 600; }
        .badge-disponible { background: #D1FAE5; color: #065F46; }
        .badge-bajo { background: #FEF3C7; color: #92400E; }
        .badge-agotado { background: #FEE2E2; color: #991B1B; }
        .acciones { display: flex; gap: 6px; }
        .accion { padding: 5px 12px; border: none; border-radius: 8px; cursor: pointer; font-size: 13px; }
        .accion-editar { background: var(--bg-soft); color: var(--primary); }
        .accion-eliminar { background: #FEE2E2; color: #DC2626; }
        .vacio { padding: 30px; text-align: center; color: var(--text-secondary); }
        .oculto { display: none; }

        .paginacion { display: flex; justify-content: center; align-items: center; gap: 16px; padding: 16px; }
        .paginacion button:disabled { opacity: .5; cursor: not-allowed; }
        .info-pagina { font-weight: 700; }

        .busquedas { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 30px; }
        .busqueda-card { padding: 20px; }
        .busqueda-card h3 { margin-bottom: 6px; }
        .busqueda-card small { color: var(--text-secondary); }
        .ayuda { font-size: 13px; color: var(--text-secondary); margin-bottom: 12px; }
        .fila { display: flex; gap: 8px; flex-wrap: wrap; }
        @media (max-width: 760px) { .busquedas { grid-template-columns: 1fr; } }

        .modal { border: none; border-radius: 16px; padding: 0; width: 440px; max-width: 94vw; box-shadow: 0 25px 60px rgba(0,0,0,.25); }
        .modal::backdrop { background: rgba(0,0,0,.45); }
        .modal-head { padding: 16px 22px; border-bottom: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center; }
        .modal-close { background: none; border: none; font-size: 22px; cursor: pointer; color: #94A3B8; }
        .modal-body { padding: 20px 22px; display: flex; flex-direction: column; gap: 8px; }
        .modal-body label { font-size: 13px; font-weight: 600; margin-top: 6px; }
        .modal-body input { padding: 10px 14px; border: 1px solid var(--border-light); border-radius: 10px; font-size: 14px; outline: none; }
        .modal-body input:focus { border-color: var(--primary); }
        .modal-body small { font-size: 12px; color: var(--text-secondary); }
        .modal-btns { display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px; }

        .toasts { position: fixed; top: 20px; right: 20px; z-index: 2000; display: flex; flex-direction: column; gap: 10px; }
        .toast { background: #fff; border-radius: 12px; padding: 12px 18px; box-shadow: 0 10px 30px rgba(0,0,0,.15); border-left: 5px solid var(--primary); font-size: 14px; }
        .toast-exito { border-left-color: var(--success); }
        .toast-error { border-left-color: var(--error); }
    </style>
</head>
<body>

<header class="angelow-header">
    <div class="header-left">
        <a href="<?= APP_URL ?>/" class="btn-back-header">← Volver</a>
        <h1>ANGELOW · Inventario <span style="color:var(--primary);font-size:14px;">(Microservicio)</span></h1>
    </div>
    <div class="estado">
        <span class="punto" id="puntoConexion"></span>
        <span id="textoConexion">Conectando…</span>
    </div>
</header>

<main>
    <h2 class="section-title">Gestión de Inventario (vía microservicio)</h2>

    <?php if (!$conExito): ?>
        <div class="aviso">
            <strong>Aviso:</strong> <?= htmlspecialchars($errorMicroservicio) ?>. La página intentará
            reconectarse automáticamente cuando el microservicio esté disponible
            (arrancarlo con: <code>mvnw.cmd spring-boot:run</code> en la carpeta del microservicio).
        </div>
    <?php endif; ?>

    <section class="stats" aria-label="Resumen">
        <div class="stat"><div class="num" id="statTotal">–</div><div class="txt">Registros totales</div></div>
        <div class="stat"><div class="num" id="statPagina">–</div><div class="txt">Página actual</div></div>
        <div class="stat"><div class="num" id="statPaginas">–</div><div class="txt">Total de páginas</div></div>
        <div class="stat"><div class="num" id="statTamano">–</div><div class="txt">Registros por página</div></div>
    </section>

    <section class="barra" aria-label="Herramientas">
        <input type="text" id="busqueda" placeholder="Buscar por nombre, categoría o estado…" maxlength="100">
        <button class="btn btn-primary" id="btnBuscar">Buscar</button>
        <button class="btn btn-soft" id="btnLimpiar">Limpiar</button>
        <label for="selectTamano">Tamaño:</label>
        <select id="selectTamano">
            <option value="5" selected>5</option>
            <option value="10">10</option>
            <option value="20">20</option>
        </select>
        <span style="flex:1"></span>
        <button class="btn btn-soft" id="btnVerTodo">Ver todo</button>
        <button class="btn btn-new" id="btnNuevo">+ Nuevo registro</button>
    </section>

    <section class="card">
        <div class="card-head">
            <h2 style="font-size:18px;">Registros de inventario</h2>
            <span class="resultado" id="infoPaginacion">Cargando…</span>
        </div>
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>Producto</th><th>Categoría</th><th>Stock total</th>
                        <th>Stock mínimo</th><th>Precio</th><th>Estado</th><th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="cuerpoTabla"></tbody>
            </table>
        </div>
        <div id="mensajeVacio" class="vacio oculto">No hay registros que mostrar.</div>
        <div class="paginacion">
            <button class="btn btn-soft" id="btnAnterior" disabled>← Anterior</button>
            <span class="info-pagina" id="infoPagina">Página 0 de 0</span>
            <button class="btn btn-soft" id="btnSiguiente" disabled>Siguiente →</button>
        </div>
    </section>

    <section class="busquedas">
        <article class="card busqueda-card">
            <h3>Búsqueda AND <small>(2 campos: nombre Y estado)</small></h3>
            <p class="ayuda">El registro debe cumplir <strong>ambas</strong> condiciones.</p>
            <div class="fila">
                <input type="text" id="andNombre" placeholder="Nombre (Ej: Body)" maxlength="255">
                <select id="andEstado">
                    <option value="DISPONIBLE">DISPONIBLE</option>
                    <option value="BAJO">BAJO</option>
                    <option value="AGOTADO">AGOTADO</option>
                </select>
                <button class="btn btn-primary" id="btnBuscarAnd">Buscar AND</button>
            </div>
        </article>
        <article class="card busqueda-card">
            <h3>Búsqueda OR <small>(3 campos: nombre O categoría O estado)</small></h3>
            <p class="ayuda">Basta <strong>una</strong> coincidencia.</p>
            <div class="fila">
                <input type="text" id="orValor" placeholder="Texto a buscar (Ej: Bebé)" maxlength="100">
                <button class="btn btn-primary" id="btnBuscarOr">Buscar OR</button>
            </div>
        </article>
    </section>
</main>

<dialog id="dialogo" class="modal">
    <div class="modal-head">
        <h2 id="dialogoTitulo">Nuevo registro de inventario</h2>
        <button type="button" class="modal-close" id="btnCerrarDialogo">&times;</button>
    </div>
    <input type="hidden" id="inventarioId">
    <form class="modal-body" id="formulario" novalidate>
        <label for="fProductoId">ID del producto *</label>
        <input type="number" id="fProductoId" min="1" step="1" placeholder="Ej: 8" required>
        <label for="fNombre">Nombre *</label>
        <input type="text" id="fNombre" maxlength="255" placeholder="Ej: Vestido de gala" required>
        <label for="fCategoria">Categoría *</label>
        <input type="text" id="fCategoria" maxlength="100" placeholder="Ej: Niñas" required>
        <label for="fStockTotal">Stock total *</label>
        <input type="number" id="fStockTotal" min="0" step="1" value="0" required>
        <label for="fStockMinimo">Stock mínimo *</label>
        <input type="number" id="fStockMinimo" min="0" step="1" value="5" required>
        <label for="fPrecio">Precio * (COP)</label>
        <input type="number" id="fPrecio" min="0" step="0.01" value="0.00" required>
        <div class="modal-btns">
            <button type="button" class="btn btn-soft" id="btnCancelarDialogo">Cancelar</button>
            <button type="submit" class="btn btn-primary" id="btnGuardar">Guardar</button>
        </div>
    </form>
</dialog>

<dialog id="modalConfirmar" class="modal">
    <div class="modal-head">
        <h2>Eliminar registro</h2>
        <button type="button" class="modal-close" id="btnCerrarConfirmar">&times;</button>
    </div>
    <div class="modal-body">
        <p id="confirmarMensaje" style="font-size:15px;"></p>
        <div class="modal-btns">
            <button type="button" class="btn btn-soft" id="btnCancelarEliminar">Cancelar</button>
            <button type="button" class="btn btn-danger" id="btnConfirmarEliminar">Eliminar</button>
        </div>
    </div>
</dialog>

<div id="toasts" class="toasts" aria-live="polite"></div>

<script>
const estado = { pagina: 0, tamano: 5, modo: 'todo', andNombre: '', andEstado: 'DISPONIBLE', orValor: '' };
const $ = (id) => document.getElementById(id);
const cuerpoTabla = $('cuerpoTabla');
const mensajeVacio = $('mensajeVacio');

async function llamarAPI(url, opciones = {}) {
    const respuesta = await fetch(API_URL + url, opciones);
    if (!respuesta.ok) {
        let mensaje = 'Error ' + respuesta.status;
        try { const c = await respuesta.json(); mensaje = c.mensaje || c.error || mensaje; } catch (e) {}
        throw new Error(mensaje);
    }
    if (respuesta.status === 204) return null;
    return respuesta.json();
}

function escapeHtml(t) { const d = document.createElement('div'); d.textContent = t == null ? '' : String(t); return d.innerHTML; }
function claseEstado(e) { const s = (e || '').toUpperCase(); if (s === 'AGOTADO') return 'badge-agotado'; if (s === 'BAJO') return 'badge-bajo'; return 'badge-disponible'; }
function formatearMoneda(v) { return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(Number(v || 0)); }

async function cargarRegistros() {
    let url = '';
    if (estado.modo === 'and') url = `/buscar/and?nombre=${encodeURIComponent(estado.andNombre)}&estado=${encodeURIComponent(estado.andEstado)}&page=${estado.pagina}&size=${estado.tamano}`;
    else if (estado.modo === 'or') url = `/buscar/or?valor=${encodeURIComponent(estado.orValor)}&page=${estado.pagina}&size=${estado.tamano}`;
    else url = `?page=${estado.pagina}&size=${estado.tamano}`;
    try {
        const datos = await llamarAPI(url);
        renderTabla(datos.content || []);
        renderPaginacion(datos);
    } catch (error) {
        mostrarToast('No se pudo consultar el inventario: ' + error.message, 'error');
        renderTabla([]);
        renderPaginacion({ page: 0, size: estado.tamano, totalElements: 0, totalPages: 0, first: true, last: true });
        $('puntoConexion').className = 'punto punto-error';
        $('textoConexion').textContent = 'Sin conexión con la API';
    }
}

function renderTabla(registros) {
    cuerpoTabla.innerHTML = '';
    if (!registros || registros.length === 0) { mensajeVacio.classList.remove('oculto'); return; }
    mensajeVacio.classList.add('oculto');
    registros.forEach(inv => {
        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td>${inv.id}</td>
            <td><strong>${escapeHtml(inv.nombre)}</strong></td>
            <td><span class="cat">${escapeHtml(inv.categoria)}</span></td>
            <td>${inv.stockTotal}</td>
            <td>${inv.stockMinimo}</td>
            <td class="precio">${formatearMoneda(inv.precio)}</td>
            <td><span class="badge ${claseEstado(inv.estado)}">${escapeHtml(inv.estado || 'DISPONIBLE')}</span></td>
            <td><div class="acciones">
                <button class="accion accion-editar" data-id="${inv.id}">Editar</button>
                <button class="accion accion-eliminar" data-id="${inv.id}">Eliminar</button>
            </div></td>`;
        cuerpoTabla.appendChild(fila);
    });
    cuerpoTabla.querySelectorAll('.accion-editar').forEach(b => b.addEventListener('click', () => editarRegistro(b.dataset.id)));
    cuerpoTabla.querySelectorAll('.accion-eliminar').forEach(b => b.addEventListener('click', () => eliminarRegistro(b.dataset.id)));
}

function renderPaginacion(datos) {
    const pagina = datos.page || 0;
    const totalPages = datos.totalPages || 0;
    const totalElements = datos.totalElements || 0;
    $('infoPagina').textContent = `Página ${pagina + 1} de ${totalPages}`;
    $('infoPaginacion').textContent = `Mostrando ${(datos.content || []).length} de ${totalElements} registros`;
    $('btnAnterior').disabled = !!(datos.first !== undefined ? datos.first : pagina <= 0);
    $('btnSiguiente').disabled = !!(datos.last !== undefined ? datos.last : pagina >= totalPages - 1);
    $('statTotal').textContent = totalElements;
    $('statPagina').textContent = (pagina + 1) + ' / ' + (totalPages || 0);
    $('statPaginas').textContent = totalPages;
    $('statTamano').textContent = datos.size || estado.tamano;
}

$('formulario').addEventListener('submit', async (e) => {
    e.preventDefault();
    const productoId = parseInt($('fProductoId').value, 10);
    const nombre = $('fNombre').value.trim();
    const categoria = $('fCategoria').value.trim();
    const stockTotal = parseInt($('fStockTotal').value, 10);
    const stockMinimo = parseInt($('fStockMinimo').value, 10);
    const precio = parseFloat($('fPrecio').value);
    if (!productoId || productoId <= 0) return mostrarToast('El ID del producto debe ser un entero positivo.', 'error');
    if (!nombre) return mostrarToast('El nombre es obligatorio.', 'error');
    if (!categoria) return mostrarToast('La categoría es obligatoria.', 'error');
    if (isNaN(stockTotal) || stockTotal < 0) return mostrarToast('El stock total no puede ser negativo.', 'error');
    if (isNaN(stockMinimo) || stockMinimo < 0) return mostrarToast('El stock mínimo no puede ser negativo.', 'error');
    if (isNaN(precio) || precio < 0) return mostrarToast('El precio no puede ser negativo.', 'error');

    const id = $('inventarioId').value;
    const cuerpo = { productoId, nombre, categoria, stockTotal, stockMinimo, precio };
    try {
        if (id) { await llamarAPI(`/${id}`, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(cuerpo) }); mostrarToast('Registro actualizado correctamente.', 'exito'); }
        else { await llamarAPI('', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(cuerpo) }); mostrarToast('Registro creado correctamente.', 'exito'); }
        cerrarModal('dialogo'); limpiarFormulario(); cargarRegistros();
    } catch (err) { mostrarToast('No se pudo guardar: ' + err.message, 'error'); }
});

async function editarRegistro(id) {
    try {
        const inv = await llamarAPI(`/${id}`);
        $('inventarioId').value = inv.id;
        $('fProductoId').value = inv.productoId;
        $('fNombre').value = inv.nombre;
        $('fCategoria').value = inv.categoria;
        $('fStockTotal').value = inv.stockTotal;
        $('fStockMinimo').value = inv.stockMinimo;
        $('fPrecio').value = inv.precio;
        $('dialogoTitulo').textContent = `Editar inventario #${inv.id}`;
        abrirModal('dialogo');
    } catch (err) { mostrarToast('No se pudo cargar el registro: ' + err.message, 'error'); }
}

function limpiarFormulario() { $('formulario').reset(); $('inventarioId').value = ''; $('dialogoTitulo').textContent = 'Nuevo registro de inventario'; }

async function eliminarRegistro(id) {
    const ok = await pedirConfirmacion(`¿Seguro que deseas eliminar el registro #${id}?`);
    if (!ok) return;
    try { await llamarAPI(`/${id}`, { method: 'DELETE' }); mostrarToast(`Registro #${id} eliminado.`, 'exito'); cargarRegistros(); }
    catch (err) { mostrarToast('No se pudo eliminar: ' + err.message, 'error'); }
}

function buscarAnd() {
    const nombre = $('andNombre').value.trim();
    if (!nombre) return mostrarToast('Escriba un nombre para la búsqueda AND.', 'error');
    estado.modo = 'and'; estado.andNombre = nombre; estado.andEstado = $('andEstado').value; estado.pagina = 0; cargarRegistros();
}
function buscarOr() {
    const valor = $('orValor').value.trim();
    if (!valor) return mostrarToast('Escriba un valor para la búsqueda OR.', 'error');
    estado.modo = 'or'; estado.orValor = valor; estado.pagina = 0; cargarRegistros();
}
function buscarGeneral() {
    const valor = $('busqueda').value.trim();
    if (!valor) return mostrarToast('Escriba algo en el buscador.', 'error');
    $('orValor').value = valor; estado.modo = 'or'; estado.orValor = valor; estado.pagina = 0; cargarRegistros();
}
function verTodo() { estado.modo = 'todo'; estado.pagina = 0; $('busqueda').value = ''; cargarRegistros(); }

$('btnAnterior').addEventListener('click', () => { if (estado.pagina > 0) { estado.pagina--; cargarRegistros(); } });
$('btnSiguiente').addEventListener('click', () => { estado.pagina++; cargarRegistros(); });
$('selectTamano').addEventListener('change', () => { estado.tamano = parseInt($('selectTamano').value, 10); estado.pagina = 0; cargarRegistros(); });
$('btnBuscar').addEventListener('click', buscarGeneral);
$('btnLimpiar').addEventListener('click', verTodo);
$('btnVerTodo').addEventListener('click', verTodo);
$('btnNuevo').addEventListener('click', () => { limpiarFormulario(); abrirModal('dialogo'); });
$('btnBuscarAnd').addEventListener('click', buscarAnd);
$('btnBuscarOr').addEventListener('click', buscarOr);
$('busqueda').addEventListener('keydown', (e) => { if (e.key === 'Enter') buscarGeneral(); });

function abrirModal(id) { $(id).showModal(); }
function cerrarModal(id) { const d = $(id); if (!d.open) return; d.close(); }
let resolverConfirmacion = null;
function pedirConfirmacion(mensaje) { return new Promise((r) => { resolverConfirmacion = r; $('confirmarMensaje').textContent = mensaje; abrirModal('modalConfirmar'); }); }
function responderConfirmacion(v) { const r = resolverConfirmacion; resolverConfirmacion = null; cerrarModal('modalConfirmar'); if (r) setTimeout(() => r(v), 0); }

$('btnCerrarDialogo').addEventListener('click', () => cerrarModal('dialogo'));
$('btnCancelarDialogo').addEventListener('click', () => cerrarModal('dialogo'));
$('btnCerrarConfirmar').addEventListener('click', () => responderConfirmacion(false));
$('btnCancelarEliminar').addEventListener('click', () => responderConfirmacion(false));
$('btnConfirmarEliminar').addEventListener('click', () => responderConfirmacion(true));
$('modalConfirmar').addEventListener('cancel', (e) => { e.preventDefault(); responderConfirmacion(false); });

function mostrarToast(texto, tipo) {
    const t = document.createElement('div');
    t.className = 'toast ' + (tipo === 'exito' ? 'toast-exito' : 'toast-error');
    t.textContent = texto;
    $('toasts').appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

async function verificarConexion() {
    try { await llamarAPI('?page=0&size=1'); $('puntoConexion').className = 'punto punto-ok'; $('textoConexion').textContent = 'Conectado al microservicio (angelow_db.inventario)'; }
    catch (e) { $('puntoConexion').className = 'punto punto-error'; $('textoConexion').textContent = 'Sin conexión con la API'; }
}

verificarConexion();
cargarRegistros();
</script>
</body>
</html>