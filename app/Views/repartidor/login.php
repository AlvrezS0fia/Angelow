<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Repartidor — ANGELOW</title>
<link rel="shortcut icon" href="<?= APP_URL ?>/assets/imagenes/general/favico.ico" type="image/x-icon">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
:root{
  --primary:#5E9DE6;--primary-dark:#3A7BC8;--accent:#7FBBF2;--accent-light:#EFF4FF;
  --bg-light:#F8FBFE;--bg-soft:#EDF4FC;--text-dark:#1E3A8A;--text-secondary:#4B6A9B;
  --white:#ffffff;--shadow:rgba(94,157,230,0.12);--shadow-hover:rgba(94,157,230,0.25);
  --border-light:#E0E7F5;--success:#10b981;--error:#ef4444;--warning:#f59e0b;
}
body{font-family:'Inter',sans-serif;background:var(--bg-light);color:var(--text-dark);min-height:100vh;display:flex;flex-direction:column;line-height:1.5;}
.toast-container{position:fixed;top:100px;right:20px;z-index:3000;display:flex;flex-direction:column;gap:12px;pointer-events:none;}
.toast{min-width:300px;max-width:420px;background:var(--white);border-radius:16px;padding:16px 20px;box-shadow:0 10px 30px rgba(0,0,0,0.15);display:flex;align-items:center;gap:14px;opacity:0;transform:translateX(100%);transition:all 0.4s;pointer-events:auto;border-left:5px solid var(--primary);}
.toast.show{opacity:1;transform:translateX(0);}
.toast.success{border-left-color:var(--success);}.toast.warning{border-left-color:var(--warning);}.toast.error{border-left-color:var(--error);}.toast.info{border-left-color:var(--primary);}
.toast-icon{width:36px;height:36px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.toast.success .toast-icon{background:var(--success);}.toast.warning .toast-icon{background:var(--warning);}.toast.error .toast-icon{background:var(--error);}
.toast-icon svg{width:20px;height:20px;stroke:white;fill:none;stroke-width:3;}
.toast-title{font-weight:600;font-size:16px;color:var(--text-dark);margin-bottom:4px;}
.toast-message{font-size:14px;color:var(--text-secondary);}
.toast-close{background:none;border:none;font-size:24px;cursor:pointer;color:var(--text-secondary);opacity:0.6;margin-left:auto;padding:0 4px;}
.toast-close:hover{opacity:1;}
.topbar{position:sticky;top:0;z-index:100;background:var(--white);border-bottom:1px solid var(--border-light);padding:12px 30px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;box-shadow:0 2px 12px var(--shadow);}
.topbar-logo{width:48px;height:48px;border-radius:50%;background:var(--bg-soft);display:flex;align-items:center;justify-content:center;overflow:hidden;cursor:pointer;position:relative;border:2px solid var(--border-light);flex-shrink:0;transition:all 0.3s;}
.topbar-logo:hover{border-color:var(--primary);box-shadow:0 0 0 4px var(--shadow);}
.topbar-logo svg{opacity:0.5;pointer-events:none;}
.topbar-brand{display:flex;flex-direction:column;line-height:1.1;}
.topbar-name{font-weight:900;font-size:22px;color:var(--primary);letter-spacing:-0.5px;}
.topbar-sub{font-size:11px;font-weight:600;color:var(--text-secondary);letter-spacing:1.5px;text-transform:uppercase;}
.btn-back-home{display:flex;align-items:center;gap:8px;padding:8px 18px;background:var(--bg-soft);border:1px solid var(--border-light);border-radius:50px;font-family:'Inter',sans-serif;font-size:13px;font-weight:600;color:var(--text-secondary);cursor:pointer;transition:all 0.25s;text-decoration:none;margin-left:auto;}
.btn-back-home:hover{background:var(--accent-light);border-color:var(--primary);color:var(--primary);transform:translateX(-2px);}
.page-body{width:100%;padding:40px 16px;display:flex;align-items:center;justify-content:center;flex:1;}
.wrapper{display:flex;width:100%;max-width:1000px;background:var(--white);border-radius:24px;box-shadow:0 8px 40px var(--shadow);overflow:hidden;border:1px solid var(--border-light);}
.sidebar{width:280px;flex-shrink:0;background:var(--text-dark);padding:36px 24px 28px;display:flex;flex-direction:column;gap:28px;position:relative;overflow:hidden;}
.sidebar::before{content:'';position:absolute;width:300px;height:300px;border-radius:50%;background:rgba(94,157,230,0.12);top:-120px;right:-100px;}
.sidebar::after{content:'';position:absolute;width:200px;height:200px;border-radius:50%;background:rgba(127,187,242,0.08);bottom:-60px;left:-50px;}
.logo-area{display:flex;flex-direction:column;align-items:center;gap:12px;position:relative;z-index:1;}
.app-name{font-weight:900;font-size:22px;color:#fff;text-align:center;letter-spacing:-0.5px;}
.app-sub{font-size:10px;font-weight:700;color:rgba(255,255,255,0.35);text-align:center;letter-spacing:1.5px;text-transform:uppercase;margin-top:-4px;}
.steps-nav{display:flex;flex-direction:column;gap:4px;position:relative;z-index:1;}
.step-item{display:flex;align-items:center;gap:14px;padding:10px 14px;border-radius:12px;transition:all 0.3s;cursor:pointer;}
.step-item:hover{background:rgba(255,255,255,0.05);}
.step-item.active{background:rgba(94,157,230,0.2);}
.step-dot{width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,0.08);border:2px solid rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;color:rgba(255,255,255,0.3);flex-shrink:0;transition:all 0.4s;}
.step-item.active .step-dot{background:var(--primary);border-color:var(--accent);color:#fff;box-shadow:0 0 20px rgba(94,157,230,0.4);}
.step-item.done .step-dot{background:var(--success);border-color:var(--success);color:#fff;}
.step-label strong{display:block;font-size:14px;font-weight:700;color:rgba(255,255,255,0.3);transition:color 0.3s;}
.step-label small{font-size:11px;color:rgba(255,255,255,0.2);}
.step-item.active .step-label strong,.step-item.done .step-label strong{color:#fff;}
.main{flex:1;padding:36px 36px 28px;display:flex;flex-direction:column;min-height:560px;background:var(--white);}
.step-heading{margin-bottom:24px;}
.step-heading h2{font-size:24px;font-weight:800;color:var(--text-dark);letter-spacing:-0.5px;}
.step-heading p{font-size:14px;color:var(--text-secondary);margin-top:4px;font-weight:500;}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px 20px;}
.full{grid-column:1/-1;}
.field{display:flex;flex-direction:column;gap:5px;}
.field-label{font-size:11px;font-weight:700;color:var(--text-secondary);letter-spacing:0.8px;text-transform:uppercase;}
.error-msg{font-size:11px;font-weight:600;color:var(--error);margin-top:2px;display:none;animation:fadeUp 0.2s ease;}
.field.invalid input,.field.invalid select{border-color:var(--error)!important;background:#fff5f5;box-shadow:0 0 0 4px rgba(239,68,68,0.08);}
.field.invalid .error-msg{display:block;}
.field.valid input,.field.valid select{border-color:var(--success)!important;background:#f0fdf4;}
input,select{width:100%;padding:12px 16px;border:2px solid var(--border-light);border-radius:12px;font-family:'Inter',sans-serif;font-size:14px;font-weight:500;color:var(--text-dark);background:var(--bg-light);outline:none;transition:all 0.25s;appearance:none;}
input:focus,select:focus{border-color:var(--primary);background:var(--white);box-shadow:0 0 0 4px var(--shadow);}
input::placeholder{color:var(--text-secondary);font-weight:400;}
.nav-btns{display:flex;justify-content:space-between;align-items:center;margin-top:auto;padding-top:24px;border-top:2px solid var(--border-light);}
.btn{display:flex;align-items:center;gap:8px;padding:12px 28px;border:none;border-radius:50px;font-family:'Inter',sans-serif;font-size:14px;font-weight:700;cursor:pointer;transition:all 0.25s;letter-spacing:0.3px;}
.btn-back{background:var(--bg-soft);color:var(--text-secondary);}
.btn-back:hover{background:var(--accent-light);color:var(--text-dark);}
.btn-next{background:var(--primary);color:#fff;box-shadow:0 4px 16px var(--shadow);}
.btn-next:hover{background:var(--primary-dark);box-shadow:0 6px 24px var(--shadow-hover);transform:translateY(-2px);}
.btn-next:disabled{opacity:0.6;cursor:not-allowed;transform:none;}
.step-count{font-size:13px;font-weight:600;color:var(--text-secondary);}
.login-form{display:flex;flex-direction:column;gap:16px;max-width:400px;}
.login-link{text-align:center;font-size:14px;color:var(--text-secondary);margin-top:16px;}
.login-link a{color:var(--primary);font-weight:600;text-decoration:none;}
.login-link a:hover{text-decoration:underline;}
.footer{background:var(--text-dark);color:rgba(255,255,255,0.4);text-align:center;font-size:12px;font-weight:600;padding:18px;letter-spacing:0.5px;border-top:1px solid rgba(255,255,255,0.05);}
@media(max-width:820px){.sidebar{display:none;}.main{padding:24px 20px 20px;}.grid{grid-template-columns:1fr;}.topbar{padding:10px 16px;}.wrapper{border-radius:16px;}}
</style>
</head>
<body>
<div class="toast-container" id="toastContainer"></div>
<header class="topbar">
  <div class="topbar-logo" title="ANGELOW">
    <svg width="20" height="20" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4" stroke="#5E9DE6" stroke-width="2"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="#5E9DE6" stroke-width="2" stroke-linecap="round"/></svg>
  </div>
  <div class="topbar-brand">
    <span class="topbar-name">ANGELOW</span>
    <span class="topbar-sub">Repartidores</span>
  </div>
  <a href="<?= APP_URL ?>/" class="btn-back-home">
    <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path d="M15 19L8 12L15 5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    Volver
  </a>
</header>

<div class="page-body">
  <div class="wrapper">
    <aside class="sidebar">
      <div class="logo-area">
        <div class="app-name">ANGELOW</div>
        <div class="app-sub">Repartidores</div>
      </div>
      <nav class="steps-nav">
        <div class="step-item active">
          <div class="step-dot"><svg width="14" height="14" fill="none" viewBox="0 0 24 24"><path d="M5 13L9 17L19 7" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
          <div class="step-label"><strong>Acceso</strong><small>Inicia sesión</small></div>
        </div>
        <div class="step-item">
          <div class="step-dot">2</div>
          <div class="step-label"><strong>Pedidos</strong><small>Trabaja y gana</small></div>
        </div>
        <div class="step-item">
          <div class="step-dot">3</div>
          <div class="step-label"><strong>Ganancias</strong><small>Retira tu dinero</small></div>
        </div>
      </nav>
    </aside>

    <main class="main">
      <div class="step-heading">
        <h2>Iniciar Sesión</h2>
        <p>Accede a tu panel de repartidor.</p>
      </div>

      <form class="login-form" id="loginForm" onsubmit="return false;">
        <div class="field" id="f-email">
          <label class="field-label">Correo Electrónico</label>
          <input id="email" type="email" placeholder="correo@ejemplo.com" autocomplete="email">
          <span class="error-msg">Ingresa un correo válido.</span>
        </div>
        <div class="field" id="f-password">
          <label class="field-label">Contraseña</label>
          <input id="password" type="password" placeholder="Tu contraseña" autocomplete="current-password">
          <span class="error-msg">Ingresa tu contraseña.</span>
        </div>
        <button type="button" class="btn btn-next" id="btnLogin" style="justify-content:center;" onclick="doLogin()">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M13.8 12H3" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Ingresar
        </button>
      </form>

      <div class="login-link">
        ¿Aún no te has registrado? <a href="<?= APP_URL ?>/repartidor/registro">Regístrate aquí</a>
      </div>
    </main>
  </div>
</div>

<footer class="footer">&copy; 2026 ANGELOW. Todos los derechos reservados.</footer>

<script>
function showToast({title, message, type = "info", duration = 4000}) {
  const container = document.getElementById("toastContainer");
  if (!container) return;
  const toast = document.createElement("div");
  toast.className = `toast ${type}`;
  const icons = { success: '<svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>', warning: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>', error: '<svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>', info: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>' };
  toast.innerHTML = `<div class="toast-icon">${icons[type] || icons.info}</div><div class="toast-content">${title ? `<div class="toast-title">${title}</div>` : ''}<div class="toast-message">${message}</div></div><button class="toast-close">×</button>`;
  container.appendChild(toast);
  setTimeout(() => toast.classList.add("show"), 100);
  toast.querySelector(".toast-close").onclick = () => { toast.classList.remove("show"); setTimeout(() => toast.remove(), 400); };
  setTimeout(() => { toast.classList.remove("show"); setTimeout(() => toast.remove(), 400); }, duration);
}

function setField(id, valid, msg) {
  const f = document.getElementById('f-' + id);
  if (!f) return valid;
  f.classList.toggle('valid', valid);
  f.classList.toggle('invalid', !valid);
  if (!valid && msg) f.querySelector('.error-msg').textContent = msg;
  return valid;
}
function val(id) { const el = document.getElementById(id); return el ? el.value.trim() : ''; }

function validateLogin() {
  let ok = true;
  ok = setField('email', /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val('email')), 'Ingresa un correo válido.') && ok;
  ok = setField('password', val('password').length > 0, 'Ingresa tu contraseña.') && ok;
  return ok;
}

function doLogin() {
  if (!validateLogin()) {
    showToast({ title: 'Corrige los errores', message: 'Revisa los campos resaltados', type: 'warning' });
    return;
  }

  const btn = document.getElementById('btnLogin');
  btn.disabled = true;
  btn.innerHTML = 'Ingresando...';

  const formData = new FormData();
  formData.append('email', val('email'));
  formData.append('password', val('password'));

  fetch('<?= APP_URL ?>/repartidor/login', {
    method: 'POST',
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      if (data.token) {
        localStorage.setItem('repartidor_token', data.token);
      }
      showToast({ title: '¡Bienvenido!', message: 'Ingreso exitoso', type: 'success', duration: 2000 });
      setTimeout(() => {
        window.location.href = data.redirect || '<?= APP_URL ?>/repartidor';
      }, 500);
    } else {
      showToast({ title: 'Error', message: data.message || 'No se pudo iniciar sesión', type: 'error' });
      btn.disabled = false;
      btn.innerHTML = '<svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M13.8 12H3" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg> Ingresar';
    }
  })
  .catch(() => {
    showToast({ title: 'Error de conexión', message: 'No pudimos conectar con el servidor', type: 'error' });
    btn.disabled = false;
    btn.innerHTML = '<svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M13.8 12H3" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg> Ingresar';
  });
}

document.getElementById('email').addEventListener('blur', () => { if (document.getElementById('f-email')) validateLogin(); });
document.getElementById('password').addEventListener('blur', () => { if (document.getElementById('f-password')) validateLogin(); });
</script>
</body>
</html>
