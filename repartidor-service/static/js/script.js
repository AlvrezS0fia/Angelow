/* ============================================================
   ANGELOW — Registro de Repartidores
   Valida por pasos y al final envía un FormData multipart al
   microservicio: POST /api/repartidor/registro
   ============================================================ */
let current = 0;
const steps = document.querySelectorAll('.form-step');
const navItems = [document.getElementById('nav0'), document.getElementById('nav1'), document.getElementById('nav2')];
const mSegs    = [document.getElementById('ms0'), document.getElementById('ms1'), document.getElementById('ms2')];
const fill = document.getElementById('progressFill');
const pct = document.getElementById('pctLabel');
const stepCount = document.getElementById('stepCount');
const today = new Date(); today.setHours(0,0,0,0);

const CATEGORIAS = ['A1','A2','B1','B2','B3','C1'];
const FOUR_CHECK = document.querySelectorAll('.checks-section input[type="checkbox"]');

function setField(id, valid, msg) {
  const f = document.getElementById('f-' + id);
  if (!f) return valid;
  f.classList.toggle('valid', valid);
  f.classList.toggle('invalid', !valid);
  if (!valid && msg) f.querySelector('.error-msg').textContent = msg;
  return valid;
}
function val(id) { const el = document.getElementById(id); return el ? el.value.trim() : ''; }
function dateVal(id) { const v = val(id); return v ? new Date(v + 'T00:00:00') : null; }

function validateStep1() {
  let ok = true;
  ok = setField('nombres',   /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/.test(val('nombres')),   'Solo letras, mínimo 2 caracteres.') && ok;
  ok = setField('apellidos', /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/.test(val('apellidos')), 'Solo letras, mínimo 2 caracteres.') && ok;
  ok = setField('tipodoc',   ['CC','CE','TI','PAS'].includes(val('tipodoc')),         'Selecciona un tipo de documento.') && ok;
  ok = setField('numdoc',    /^\d{5,12}$/.test(val('numdoc')),                       'Solo números, entre 5 y 12 dígitos.') && ok;
  ok = setField('celular',   /^3\d{9}$/.test(val('celular').replace(/\s/g,'')),       'Número colombiano válido (10 dígitos, empieza en 3).') && ok;
  ok = setField('correo',    /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val('correo')),        'Ingresa un correo válido.') && ok;
  ok = setField('correo2',   val('correo') === val('correo2') && val('correo2') !== '', 'Los correos no coinciden.') && ok;
  ok = setField('pass',      /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~´`]).{8,}$/.test(val('pass')), 'Mínimo 8 caracteres con mayúscula, minúscula, número y especial.') && ok;
  ok = setField('pass2',     val('pass') === val('pass2') && val('pass2') !== '',     'Las contraseñas no coinciden.') && ok;
  return ok;
}

function validateStep2() {
  let ok = true;
  ok = setField('vehiculo', ['moto','bicicleta','carro','camioneta'].includes(val('vehiculo')), 'Selecciona un tipo de vehículo.') && ok;
  const esBici = val('vehiculo') === 'bicicleta';
  if (!esBici) {
    ok = setField('placa', /^[A-Za-z]{3}\d{3}$|^[A-Za-z]{3}\d{2}[A-Za-z]$/.test(val('placa').replace(/-/g,'')), 'Formato válido: ABC123 o ABC12D.') && ok;
    ok = setField('venlicencia', setVisVal('venlicencia') || invalidFuture('venlicencia', 'La licencia no puede estar vencida.'), '') && ok;
  } else {
    setField('placa', true, ''); document.getElementById('f-placa').classList.remove('invalid','valid');
    setField('venlicencia', setVisVal('venlicencia') || true, '');
  }
  ok = setField('licencia',   /^\d{5,15}$/.test(val('licencia')),                        'Solo números, entre 5 y 15 dígitos.') && ok;
  ok = setField('catlicencia', CATEGORIAS.includes(val('catlicencia').toUpperCase()),     'Categoría válida: A1, A2, B1, B2, B3, C1.') && ok;
  ok = setField('tarjeta',    esBici || /^\d{6,15}$/.test(val('tarjeta')),               'Solo números, entre 6 y 15 dígitos.') && ok;
  if (esBici) { setField('vensoat', true, ''); setField('tarjeta', true, ''); }
  else {
    ok = setField('vensoat', invalidFuture('vensoat', 'El SOAT no puede estar vencido.'), 'El SOAT no puede estar vencido.') && ok;
  }
  return ok;
}

function setVisVal(id) { return document.getElementById(id) && document.getElementById(id).value.trim() !== ''; }
function invalidFuture(id, msg) {
  const d = dateVal(id);
  const f = document.getElementById('f-' + id);
  if (!d) return false;
  const valid = d >= today;
  if (f) { f.classList.toggle('valid', valid); f.classList.toggle('invalid', !valid); }
  return valid;
}

function validateStep3() {
  let ok = true;
  [['soat-file','f-soatfile'],['tarjeta-file','f-tarjetafile'],['licencia-file','f-licenciafile']].forEach(([fid, wid]) => {
    const inp = document.getElementById(fid);
    const fld = document.getElementById(wid);
    if (!inp || !fld) return;
    const has = inp.files && inp.files.length > 0;
    fld.classList.toggle('valid', has);
    fld.classList.toggle('invalid', !has);
    if (!has) ok = false;
  });
  FOUR_CHECK.forEach(c => {
    const item = c.closest('.check-item');
    if (!c.checked) { item.style.borderColor='#ef4444'; item.style.background='#fff5f5'; item.classList.remove('checked'); ok=false; }
    else            { item.style.borderColor=''; item.style.background=''; item.classList.add('checked'); }
  });
  return ok;
}

function update() {
  steps.forEach((s,i) => s.classList.toggle('active', i === current));
  navItems.forEach((n,i) => {
    n.classList.remove('active','done');
    if (i === current) n.classList.add('active');
    else if (i < current) n.classList.add('done');
    const dot = n.querySelector('.step-dot');
    dot.innerHTML = i < current
      ? `<i class="fa-solid fa-check"></i>`
      : i+1;
  });
  mSegs.forEach((s,i) => {
    s.classList.remove('active','done');
    if (i === current) s.classList.add('active');
    else if (i < current) s.classList.add('done');
  });
  const p = Math.round(((current+1)/steps.length)*100);
  fill.style.width = p+'%'; pct.textContent = p+'%';
  stepCount.textContent = `Paso ${current+1} de ${steps.length}`;
}

function nextStep() {
  const validators = [validateStep1, validateStep2, validateStep3];
  if (!validators[current]()) {
    const btn = document.querySelector('.btn-next');
    btn.style.animation = 'shake .4s ease';
    setTimeout(() => btn.style.animation = '', 400);
    return;
  }
  if (current < steps.length - 1) { current++; update(); }
  else { submitForm(); }
}

function prevStep() { if (current > 0) { current--; update(); } }

function showName(input, spanId, fieldId) {
  if (input.files && input.files[0]) {
    const name = input.files[0].name;
    const span = document.getElementById(spanId);
    span.textContent = '✓ ' + (name.length > 22 ? name.substring(0,22)+'...' : name);
    span.style.color = '#0d9668';
    const fld = document.getElementById(fieldId);
    if (fld) { fld.classList.add('valid'); fld.classList.remove('invalid'); }
  }
}

/* ── ENVÍO AL MICROSERVICIO ─────────────────────────────── */
function submitForm() {
  const btn = document.getElementById('btnEnviar');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enviando...';

  const fd = new FormData();
  fd.append('nombres', val('nombres'));
  fd.append('apellidos', val('apellidos'));
  fd.append('tipodoc', val('tipodoc'));
  fd.append('numdoc', val('numdoc'));
  fd.append('celular', val('celular').replace(/\s/g,''));
  fd.append('correo', val('correo'));
  fd.append('pass', document.getElementById('pass').value);
  fd.append('pass_confirm', document.getElementById('pass2').value);

  fd.append('vehiculo', val('vehiculo'));
  fd.append('placa', val('placa'));
  fd.append('licencia', val('licencia'));
  fd.append('catlicencia', val('catlicencia').toUpperCase());
  fd.append('tarjeta', val('tarjeta'));
  fd.append('vensoat', val('vensoat'));
  fd.append('venlicencia', val('venlicencia'));

  const chk = document.querySelector('.c-terminos');
  const chkP = document.querySelector('.c-privacidad');
  fd.append('acepta_terminos', chk && chk.checked ? '1' : '0');
  fd.append('acepta_privacidad', chkP && chkP.checked ? '1' : '0');

  [['soat-file','soat-file'],['tarjeta-file','tarjeta-file'],['licencia-file','licencia-file']].forEach(([id, name]) => {
    const inp = document.getElementById(id);
    if (inp && inp.files && inp.files[0]) fd.append(name, inp.files[0], inp.files[0].name);
  });

  fetch('/api/repartidor/registro', { method: 'POST', body: fd })
    .then(async res => {
      let body = null;
      try { body = await res.json(); } catch (e) { /* respuesta no-JSON */ }
      if (!res.ok) {
        const msg = body && body.detail ? String(body.detail) : 'Error inesperado del servidor.';
        showServerError(msg);
        return;
      }
      showSuccess();
    })
    .catch(() => showServerError('No se pudo conectar con el servidor. Verifica tu conexión e inténtalo de nuevo.'))
    .finally(() => {
      btn.disabled = false;
      btn.innerHTML = 'Enviar solicitud <i class="fa-solid fa-paper-plane"></i>';
    });
}

function showSuccess() {
  steps.forEach(s => s.classList.remove('active'));
  document.getElementById('successScreen').classList.add('active');
  document.getElementById('errorScreen').classList.remove('active');
  document.getElementById('navBtns').style.display = 'none';
  fill.style.width = '100%'; pct.textContent = '100%';
  navItems.forEach(n => { n.classList.remove('active'); n.classList.add('done'); });
  mSegs.forEach(s => { s.classList.remove('active'); s.classList.add('done'); });
}

function showServerError(detail) {
  const box = document.getElementById('errorBox');
  const items = detail.split(' | ').map(t => '• ' + t).join('<br>');
  box.innerHTML = items || detail;
  steps.forEach(s => s.classList.remove('active'));
  document.getElementById('errorScreen').classList.add('active');
  document.getElementById('navBtns').style.display = 'none';
}

function backToForm() {
  document.getElementById('errorScreen').classList.remove('active');
  document.getElementById('successScreen').classList.remove('active');
  document.getElementById('navBtns').style.display = 'flex';
  current = steps.length - 1;
  update();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ── LOGIN MODAL ───────────────────────────────────────── */
const ANGELOW_BASE = 'http://localhost/Angelow/public';

function openLoginModal() {
  const overlay = document.getElementById('loginModalOverlay');
  if (!overlay) return;
  overlay.classList.add('show');
  document.getElementById('lm-email').value = '';
  document.getElementById('lm-password').value = '';
  document.getElementById('lm-alert').classList.remove('show');
  document.getElementById('lm-alert').textContent = '';
  ['lm-f-email','lm-f-password'].forEach(function(id) {
    var f = document.getElementById(id);
    if (f) { f.classList.remove('valid','invalid'); }
  });
  setTimeout(function() { document.getElementById('lm-email').focus(); }, 100);
}

function closeLoginModal(e) {
  if (e && e.target !== e.currentTarget) return;
  var overlay = document.getElementById('loginModalOverlay');
  if (overlay) overlay.classList.remove('show');
}

function lmVal(id) { var el = document.getElementById(id); return el ? el.value.trim() : ''; }

function lmSetField(id, valid, msg) {
  var f = document.getElementById(id);
  if (!f) return valid;
  f.classList.toggle('valid', valid);
  f.classList.toggle('invalid', !valid);
  if (!valid && msg) f.querySelector('.login-modal-error').textContent = msg;
  return valid;
}

function doLoginFromModal() {
  var ok = true;
  ok = lmSetField('lm-f-email', /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(lmVal('lm-email')), 'Ingresa un correo válido.') && ok;
  ok = lmSetField('lm-f-password', lmVal('lm-password').length > 0, 'Ingresa tu contraseña.') && ok;

  var alertEl = document.getElementById('lm-alert');
  alertEl.classList.remove('show');
  alertEl.textContent = '';

  if (!ok) return;

  var btn = document.getElementById('lm-btnLogin');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Ingresando...';

  fetch('/api/repartidor/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
    body: JSON.stringify({ email: lmVal('lm-email'), password: lmVal('lm-password') })
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (data.success) {
      if (data.token) localStorage.setItem('repartidor_token', data.token);
      window.location.href = window.location.origin + '/dashboard';
    } else {
      alertEl.textContent = data.message || 'Correo o contraseña incorrectos.';
      alertEl.classList.add('show');
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-right-to-bracket"></i> Iniciar Sesión';
    }
  })
  .catch(function() {
    alertEl.textContent = 'Error de conexión. Verifica tu red e inténtalo de nuevo.';
    alertEl.classList.add('show');
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-right-to-bracket"></i> Iniciar Sesión';
  });
}

/* ── REAL-TIME FEEDBACK ──────────────────────────────────── */
document.getElementById('lm-email').addEventListener('blur', function() { lmSetField('lm-f-email', /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(lmVal('lm-email')), 'Ingresa un correo válido.'); });
document.getElementById('lm-password').addEventListener('blur', function() { lmSetField('lm-f-password', lmVal('lm-password').length > 0, 'Ingresa tu contraseña.'); });

document.querySelectorAll('input, select').forEach(function(el) {
  el.addEventListener('blur', function() { if (current===0) validateStep1(); if (current===1) validateStep2(); });
  if (el.id==='placa')    el.addEventListener('input', function() { el.value = el.value.toUpperCase(); });
  if (['celular','numdoc','licencia','tarjeta'].indexOf(el.id) !== -1)
    el.addEventListener('input', function() { el.value = el.value.replace(/[^\d]/g,''); });
  if (['nombres','apellidos'].indexOf(el.id) !== -1)
    el.addEventListener('input', function() { el.value = el.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g,''); });
});

/* ── CHECKBOX CUSTOM STATE ───────────────────────────────── */
document.querySelectorAll('.cbx input[type="checkbox"]').forEach(function(c) {
  c.addEventListener('change', function() {
    var item = c.closest('.check-item');
    item.classList.toggle('checked', c.checked);
    if (c.checked) { item.style.borderColor=''; item.style.background=''; }
  });
});

/* ── SHAKE KEYFRAME ──────────────────────────────────────── */
var shakeStyle = document.createElement('style');
shakeStyle.textContent = '@keyframes shake{0%,100%{transform:translateX(0)}20%{transform:translateX(-6px)}40%{transform:translateX(6px)}60%{transform:translateX(-4px)}80%{transform:translateX(4px)}}';
document.head.appendChild(shakeStyle);

update();
