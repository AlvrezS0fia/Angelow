<!--
 |========================================================================
 | VISTA: cargador/index.php
 |------------------------------------------------------------------------
 | QUÉ MUESTRA: Pantalla de carga animada (loader) que se muestra antes de
 | entrar a la tienda; va descargando fuentes, estilos, scripts y recursos
 | y muestra el progreso con una barra animada.
 |
 | ARCHIVOS EXTERNOS: Los estilos y la lógica de carga viven en este mismo
 | archivo. En tiempo real carga bienvenida.css, carrusel.css, Chart.js,
 | Leaflet, jsPDF y los assets de la tienda.
 |
 | JS QUE LA CONTROLA: el bloque <script> inline de este archivo (función
 | iniciarCarga y las funciones auxiliares de precarga).
 |========================================================================
-->
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ANGELOW</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Quicksand:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root{
    --sky-top:#a8d8ea;
    --sky-mid:#72bcd4;
    --sky-deep:#4a9ab5;
    --ink-navy:#1d5a87;
    --ink-navy-soft:#3d7ba3;
    --cloud-white:#5e9de6;
    --cloud-shadow:#4a9ab5;
    --glow:#4a9ab5;
  }
  *{margin:0;padding:0;box-sizing:border-box;}
  html,body{
    width:100%;height:100%;
    overflow:hidden;
    background:linear-gradient(180deg,var(--sky-top) 0%, var(--sky-mid) 55%, var(--sky-deep) 100%);
    font-family:'Quicksand',sans-serif;
  }
  .stage{
    position:relative;
    width:100%;height:100vh;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    isolation:isolate;
  }
  .star{
    position:absolute;
    width:10px;height:10px;
    z-index:1;
    opacity:0;
    animation:twinkle 3.2s ease-in-out infinite;
  }
  .star svg{width:100%;height:100%;fill:#ffffff;filter:drop-shadow(0 0 4px rgba(255, 255, 255, 0.9));}
  @keyframes twinkle{
    0%,100%{opacity:0; transform:scale(.4) rotate(0deg);}
    50%{opacity:1; transform:scale(1) rotate(20deg);}
  }
  .cloud-layer{
    position:absolute;
    bottom:0;
    display:flex;
    align-items:flex-end;
    gap:120px;
    white-space:nowrap;
  }
  .layer-back{ z-index:0; opacity:.55; animation:layerFloat 8s ease-in-out infinite; bottom:8%; }
  .layer-mid{ z-index:0; opacity:.75; animation:layerFloat 6s ease-in-out infinite .5s; bottom:2%; }
  .layer-front{ z-index:2; opacity:.95; animation:layerFloat 5s ease-in-out infinite 1s; bottom:-6%; }
  @keyframes layerFloat{
    0%,100%{ transform:translateY(0); }
    50%{ transform:translateY(-12px); }
  }
  .cloud{
    display:inline-block;
    will-change:transform;
    animation:cloudFloat 4s ease-in-out infinite;
  }
  .cloud:nth-child(2){ animation-delay:.5s; animation-duration:4.8s; }
  .cloud:nth-child(3){ animation-delay:1s; animation-duration:5.6s; }
  @keyframes cloudFloat{
    0%,100%{ transform:translateY(0); }
    50%{ transform:translateY(-18px); }
  }
  .cloud svg{
    display:block;
    fill:#5e9de6;
    shape-rendering:geometricPrecision;
  }
  .center{
    position:relative;
    z-index:3;
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:26px;
  }
  .halo-wrap{
    position:relative;
    width:230px;
    height:230px;
    display:flex;
    align-items:center;
    justify-content:center;
  }
  .breath-halo{
    position:absolute;
    width:100%;
    height:100%;
    border-radius:50%;
    background:radial-gradient(circle, rgba(74,154,181,.9) 0%, rgba(74,154,181,.35) 45%, rgba(74,154,181,0) 72%);
    will-change:transform,opacity;
    animation: breathe 2.8s ease-in-out infinite;
  }
  @keyframes breathe{
    0%,100%{ transform:scale(.78); opacity:.5; }
    50%{ transform:scale(1.2); opacity:1; }
  }
  .logo-float{
    position:relative;
    width:190px;
    height:190px;
    border-radius:50%;
    overflow:hidden;
    box-shadow:0 12px 30px rgba(29,90,135,.28), 0 0 0 6px rgba(74,154,181,.6);
    will-change:transform;
    animation: floaty 2.8s ease-in-out infinite;
    background:var(--cloud-white);
  }
  .logo-float img{
    width:100%;height:100%;
    object-fit:cover;
    display:block;
  }
  @keyframes floaty{
    0%,100%{ transform:translateY(0px) rotate(0deg); }
    25%{ transform:translateY(-10px) rotate(1.5deg); }
    50%{ transform:translateY(-20px) rotate(0deg); }
    75%{ transform:translateY(-10px) rotate(-1.5deg); }
  }
  .orbit-sparkle{
    position:absolute;
    width:230px;height:230px;
    top:0;left:0;
    animation: spin 5s linear infinite;
  }
  .orbit-sparkle svg{
    position:absolute;
    top:-2px;
    left:50%;
    width:16px;height:16px;
    transform:translateX(-50%);
    fill:#4a9ab5;
    filter:drop-shadow(0 0 5px rgba(74,154,181,.95));
  }
  @keyframes spin{
    from{ transform:rotate(0deg); }
    to{ transform:rotate(360deg); }
  }
  .orbit-sparkle-2{
    animation: spinReverse 4s linear infinite;
  }
  .orbit-sparkle-2 svg{
    fill:#5e9de6;
    filter:drop-shadow(0 0 6px rgba(94,157,230,.9));
    top:auto; bottom:-2px;
  }
  @keyframes spinReverse{
    from{ transform:rotate(360deg); }
    to{ transform:rotate(0deg); }
  }
  .label{
    text-align:center;
  }
  .label h1{
    font-family:'Fredoka',sans-serif;
    font-weight:600;
    font-size:15px;
    letter-spacing:.12em;
    color:var(--ink-navy);
    text-transform:uppercase;
    margin-bottom:6px;
  }
  .label p{
    font-family:'Quicksand',sans-serif;
    font-weight:500;
    font-size:14px;
    color:var(--ink-navy-soft);
  }
  .dots span{
    display:inline-block;
    animation: dotPulse 1.4s ease-in-out infinite;
  }
  .dots span:nth-child(2){ animation-delay:.2s; }
  .dots span:nth-child(3){ animation-delay:.4s; }
  @keyframes dotPulse{
    0%,60%,100%{ opacity:.25; }
    30%{ opacity:1; }
  }
  .progress-track{
    position:relative;
    width:220px;
    height:12px;
    border-radius:999px;
    background:rgba(74,154,181,.55);
    box-shadow:inset 0 1px 3px rgba(29,90,135,.18);
    overflow:hidden;
  }
  .progress-fill{
    position:absolute;
    top:0; left:0; bottom:0;
    width:40%;
    border-radius:999px;
    background:linear-gradient(90deg, var(--sky-deep), var(--ink-navy-soft));
    animation: loadSweep 1.8s ease-in-out infinite;
  }
  @keyframes loadSweep{
    0%{ transform:translateX(-100%); width:40%; }
    50%{ transform:translateX(30%); width:55%; }
    100%{ transform:translateX(150%); width:40%; }
  }
  @media (prefers-reduced-motion: reduce){
    *{ animation-duration:.001s !important; animation-iteration-count:1 !important; }
  }
  #statusText{
    transition:opacity .25s;
  }
  @media (max-width:420px){
    .halo-wrap{ width:180px; height:180px; }
    .logo-float{ width:150px; height:150px; }
    .orbit-sparkle{ width:180px; height:180px; }
    .cloud-layer{ gap:70px; }
  }
</style>
</head>
<body>

<div class="stage">

  <!-- SECCIÓN: Estrellas decorativas animadas repartidas por el fondo -->
  <div class="star" style="top:12%; left:14%; animation-delay:.2s;"><svg viewBox="0 0 24 24"><path d="M12 0 L14 10 L24 12 L14 14 L12 24 L10 14 L0 12 L10 10 Z"/></svg></div>
  <div class="star" style="top:20%; left:78%; animation-delay:1.1s;"><svg viewBox="0 0 24 24"><path d="M12 0 L14 10 L24 12 L14 14 L12 24 L10 14 L0 12 L10 10 Z"/></svg></div>
  <div class="star" style="top:65%; left:8%; animation-delay:.6s;"><svg viewBox="0 0 24 24"><path d="M12 0 L14 10 L24 12 L14 14 L12 24 L10 14 L0 12 L10 10 Z"/></svg></div>
  <div class="star" style="top:70%; left:88%; animation-delay:1.6s;"><svg viewBox="0 0 24 24"><path d="M12 0 L14 10 L24 12 L14 14 L12 24 L10 14 L0 12 L10 10 Z"/></svg></div>
  <div class="star" style="top:8%; left:48%; animation-delay:2.1s;"><svg viewBox="0 0 24 24"><path d="M12 0 L14 10 L24 12 L14 14 L12 24 L10 14 L0 12 L10 10 Z"/></svg></div>
  <div class="star" style="top:40%; left:5%; animation-delay:1.8s;"><svg viewBox="0 0 24 24"><path d="M12 0 L14 10 L24 12 L14 14 L12 24 L10 14 L0 12 L10 10 Z"/></svg></div>
  <div class="star" style="top:45%; left:92%; animation-delay:.9s;"><svg viewBox="0 0 24 24"><path d="M12 0 L14 10 L24 12 L14 14 L12 24 L10 14 L0 12 L10 10 Z"/></svg></div>

  <!-- SECCIÓN: Capa trasera de nubes animadas -->
  <div class="cloud-layer layer-back">
    <span class="cloud"><svg width="160" height="70" viewBox="0 0 160 70"><path d="M20 60 Q0 60 0 42 Q0 26 18 26 Q20 8 42 8 Q62 8 66 24 Q80 16 96 24 Q116 20 122 38 Q142 36 142 54 Q142 60 132 60 Z" fill="#5e9de6"/></svg></span>
    <span class="cloud"><svg width="130" height="56" viewBox="0 0 130 56"><path d="M16 48 Q0 48 0 34 Q0 21 15 21 Q17 6 34 6 Q50 6 53 19 Q64 13 78 19 Q94 16 99 31 Q115 29 115 43 Q115 48 106 48 Z" fill="#5e9de6"/></svg></span>
    <span class="cloud"><svg width="160" height="70" viewBox="0 0 160 70"><path d="M20 60 Q0 60 0 42 Q0 26 18 26 Q20 8 42 8 Q62 8 66 24 Q80 16 96 24 Q116 20 122 38 Q142 36 142 54 Q142 60 132 60 Z" fill="#5e9de6"/></svg></span>
  </div>

  <div class="cloud-layer layer-mid">
    <span class="cloud"><svg width="200" height="86" viewBox="0 0 200 86"><path d="M26 74 Q0 74 0 52 Q0 32 22 32 Q25 10 52 10 Q78 10 83 30 Q100 20 120 30 Q145 25 153 47 Q178 45 178 66 Q178 74 165 74 Z" fill="#5e9de6"/></svg></span>
    <span class="cloud"><svg width="150" height="64" viewBox="0 0 150 64"><path d="M20 55 Q0 55 0 39 Q0 24 17 24 Q19 8 39 8 Q59 8 62 22 Q75 15 90 22 Q109 19 114 36 Q133 34 133 49 Q133 55 122 55 Z" fill="#5e9de6"/></svg></span>
  </div>

  <!-- SECCIÓN: Contenido central con el logo flotante, el texto de estado y la barra de progreso -->
  <div class="center">
    <div class="halo-wrap">
      <div class="breath-halo"></div>
      <div class="orbit-sparkle">
        <svg viewBox="0 0 24 24"><path d="M12 0 L14 10 L24 12 L14 14 L12 24 L10 14 L0 12 L10 10 Z"/></svg>
      </div>
      <div class="orbit-sparkle orbit-sparkle-2">
        <svg viewBox="0 0 24 24"><path d="M12 0 L14 10 L24 12 L14 14 L12 24 L10 14 L0 12 L10 10 Z"/></svg>
      </div>
      <div class="logo-float">
        <img src="<?= APP_URL ?>/assets/imagenes/general/logos.png" alt="ANGELOW" onerror="this.style.display='none';this.parentElement.innerHTML='<span style=font-size:60px;font-weight:700;color:#1d5a87;font-family:Fredoka,sans-serif;>ANGELOW</span>'">
      </div>
    </div>

    <div class="label">
      <h1>Ropa de ni&ntilde;os</h1>
      <p id="statusText">Preparando tu experiencia
        <span class="dots"><span>.</span><span>.</span><span>.</span></span>
      </p>
    </div>

    <div class="progress-track">
      <div class="progress-fill" id="progressFill"></div>
    </div>
  </div>

  <!-- SECCIÓN: Capa frontal de nubes animadas que cierra la escena -->
  <div class="cloud-layer layer-front">
    <span class="cloud"><svg width="260" height="110" viewBox="0 0 260 110"><path d="M34 96 Q0 96 0 68 Q0 42 30 42 Q34 12 70 12 Q104 12 112 40 Q134 26 160 40 Q194 32 206 62 Q238 58 238 86 Q238 96 220 96 Z" fill="#5e9de6"/></svg></span>
    <span class="cloud"><svg width="200" height="88" viewBox="0 0 200 88"><path d="M26 76 Q0 76 0 54 Q0 33 23 33 Q26 10 53 10 Q79 10 85 31 Q102 20 122 31 Q148 25 156 48 Q182 45 182 68 Q182 76 168 76 Z" fill="#5e9de6"/></svg></span>
    <span class="cloud"><svg width="260" height="110" viewBox="0 0 260 110"><path d="M34 96 Q0 96 0 68 Q0 42 30 42 Q34 12 70 12 Q104 12 112 40 Q134 26 160 40 Q194 32 206 62 Q238 58 238 86 Q238 96 220 96 Z" fill="#5e9de6"/></svg></span>
  </div>

</div>

<script>
(function () {
  'use strict';

  // Configuración del cargador: URL base, tiempo máximo por recurso y tiempos de espera
  var CONFIG = {
    baseUrl: '<?= APP_URL ?>',
    timeout: 8000,
    minDisplayTime: 3000,
    transitionDelay: 400,
  };

  var progressFill = document.getElementById('progressFill');
  var statusText = document.getElementById('statusText');
  var totalPromises = 0;
  var resolvedCount = 0;
  var failedCount = 0;

  // Estado del progreso: se cuentan las promesas resueltas y fallidas para mover la barra
  function updateProgress() {
    var done = resolvedCount + failedCount;
    var pct = Math.min(Math.round((done / totalPromises) * 100), 100);
    if (progressFill) {
      progressFill.style.width = pct + '%';
    }
  }

  function setStatus(msg) {
    if (statusText) {
      statusText.style.opacity = '0';
      setTimeout(function () {
        statusText.textContent = msg;
        statusText.style.opacity = '1';
      }, 200);
    }
  }

  function withTimeout(promise, ms) {
    return new Promise(function (resolve, reject) {
      var timer = setTimeout(function () {
        reject(new Error('Timeout despues de ' + ms + 'ms'));
      }, ms);
      promise.then(
        function (val) { clearTimeout(timer); resolve(val); },
        function (err) { clearTimeout(timer); reject(err); }
      );
    });
  }

  function cargarFuentes() {
    return new Promise(function (resolve, reject) {
      if (document.fonts && document.fonts.check('16px Inter') && document.fonts.check('16px Quicksand')) {
        resolve('fonts-already-loaded');
        return;
      }
      var link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Quicksand:wght@400;500;600&display=swap';
      link.onload = function () {
        if (document.fonts && document.fonts.ready) {
          document.fonts.ready.then(function () { resolve('fonts-loaded'); });
        } else {
          resolve('fonts-loaded');
        }
      };
      link.onerror = function () { reject(new Error('Error cargando Google Fonts')); };
      document.head.appendChild(link);
    });
  }

  function cargarIconos() {
    return new Promise(function (resolve, reject) {
      if (document.querySelector('link[href*="font-awesome"]')) {
        resolve('icons-already-loaded');
        return;
      }
      var link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
      link.onload = function () { resolve('icons-loaded'); };
      link.onerror = function () { reject(new Error('Error cargando Font Awesome')); };
      document.head.appendChild(link);
    });
  }

  function cargarEstilosPrincipales() {
    return new Promise(function (resolve, reject) {
      var styles = [
        CONFIG.baseUrl + '/assets/css/bienvenida.css',
        CONFIG.baseUrl + '/assets/css/carrusel.css'
      ];
      var loaded = 0;
      function onStyleLoad() {
        loaded++;
        if (loaded === styles.length) resolve('styles-loaded');
      }
      styles.forEach(function (href) {
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = href;
        link.onload = onStyleLoad;
        link.onerror = function () { onStyleLoad(); };
        document.head.appendChild(link);
      });
    });
  }

  function cargarLogo() {
    return new Promise(function (resolve, reject) {
      var img = new Image();
      img.onload = function () { resolve('logo-loaded'); };
      img.onerror = function () { resolve('logo-fallback'); };
      img.src = CONFIG.baseUrl + '/assets/imagenes/general/logos.png';
    });
  }

  function cargarScriptPrincipal() {
    return new Promise(function (resolve, reject) {
      var script = document.createElement('script');
      script.src = CONFIG.baseUrl + '/assets/js/bienvenida.js';
      script.onload = function () { resolve('main-script-loaded'); };
      script.onerror = function () { reject(new Error('Error cargando bienvenida.js')); };
      document.body.appendChild(script);
    });
  }

  function cargarCarrusel() {
    return new Promise(function (resolve, reject) {
      var script = document.createElement('script');
      script.src = CONFIG.baseUrl + '/assets/js/carrusel.js';
      script.onload = function () { resolve('carousel-loaded'); };
      script.onerror = function () { reject(new Error('Error cargando carrusel.js')); };
      document.body.appendChild(script);
    });
  }

  function cargarDatosSesion() {
    return new Promise(function (resolve) {
      setTimeout(function () {
        try {
          var userData = localStorage.getItem('angelow_user');
          var cartData = localStorage.getItem('angelow_cart');
          var favData = localStorage.getItem('angelow_favorites');
          resolve({
            user: userData ? JSON.parse(userData) : null,
            hasCart: cartData ? JSON.parse(cartData).length > 0 : false,
            favoritesCount: favData ? JSON.parse(favData).length : 0
          });
        } catch (e) {
          resolve({ user: null, hasCart: false, favoritesCount: 0 });
        }
      }, 300);
    });
  }

  function cargarGraficos() {
    return new Promise(function (resolve) {
      var script = document.createElement('script');
      script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
      script.onload = function () { resolve('charts-loaded'); };
      script.onerror = function () { resolve('charts-skipped'); };
      document.head.appendChild(script);
    });
  }

  function cargarMapas() {
    return new Promise(function (resolve) {
      var loaded = 0;
      var total = 2;
      function checkDone() { loaded++; if (loaded === total) resolve('maps-loaded'); }
      var cssLink = document.createElement('link');
      cssLink.rel = 'stylesheet';
      cssLink.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
      cssLink.onload = checkDone;
      cssLink.onerror = function () { checkDone(); };
      document.head.appendChild(cssLink);
      var jsScript = document.createElement('script');
      jsScript.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
      jsScript.onload = checkDone;
      jsScript.onerror = function () { checkDone(); };
      document.head.appendChild(jsScript);
    });
  }

  function cargarPDF() {
    return new Promise(function (resolve) {
      var loaded = 0;
      var total = 2;
      function checkDone() { loaded++; if (loaded === total) resolve('pdf-loaded'); }
      var jspdf = document.createElement('script');
      jspdf.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
      jspdf.onload = checkDone;
      jspdf.onerror = function () { checkDone(); };
      document.head.appendChild(jspdf);
      var autotable = document.createElement('script');
      autotable.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js';
      autotable.onload = checkDone;
      autotable.onerror = function () { checkDone(); };
      document.head.appendChild(autotable);
    });
  }

  function cargarImagenesProducto() {
    return new Promise(function (resolve) {
      var imagenes = [
        '/assets/imagenes/ninos/Frente Conjunto Deportivo.png',
        '/assets/imagenes/ninos/Frente Conjunto Size.png',
        '/assets/imagenes/ninos/Frente Body Nino.png',
        '/assets/imagenes/ninos/Frente Jogger.png',
        '/assets/imagenes/ninas/Frente Conjunto Infantil.png',
        '/assets/imagenes/ninas/Frente Body Negro.png',
        '/assets/imagenes/ninas/Frente Set Falda.png',
        '/assets/imagenes/bebe/Frente Set Bebe.png',
        '/assets/imagenes/general/logos.png',
        '/assets/imagenes/general/carro.png',
        '/assets/imagenes/general/favoritos.png',
        '/assets/imagenes/general/avatar.png'
      ];
      var loaded = 0;
      var total = imagenes.length;
      if (total === 0) { resolve('images-loaded'); return; }
      function checkDone() { loaded++; if (loaded === total) resolve('images-loaded'); }
      imagenes.forEach(function (path) {
        var img = new Image();
        img.onload = checkDone;
        img.onerror = checkDone;
        img.src = CONFIG.baseUrl + path;
      });
    });
  }

  // Lanza la descarga de todos los recursos y al terminar redirige a la tienda
  function iniciarCarga() {
    var promesas = [
      { id: 'fuentes',    fn: cargarFuentes,             critica: true,  label: 'Cargando fuentes...' },
      { id: 'iconos',     fn: cargarIconos,              critica: true,  label: 'Cargando iconos...' },
      { id: 'estilos',    fn: cargarEstilosPrincipales,  critica: true,  label: 'Cargando estilos...' },
      { id: 'logo',       fn: cargarLogo,                critica: true,  label: 'Cargando logo...' },
      { id: 'script',     fn: cargarScriptPrincipal,     critica: true,  label: 'Cargando tienda...' },
      { id: 'carrusel',   fn: cargarCarrusel,            critica: true,  label: 'Cargando carrusel...' },
      { id: 'sesion',     fn: cargarDatosSesion,         critica: false, label: 'Verificando sesion...' },
      { id: 'graficos',   fn: cargarGraficos,            critica: false, label: 'Cargando graficos...' },
      { id: 'mapas',      fn: cargarMapas,               critica: false, label: 'Cargando mapas...' },
      { id: 'pdf',        fn: cargarPDF,                 critica: false, label: 'Cargando generador PDF...' },
      { id: 'imagenes',   fn: cargarImagenesProducto,    critica: false, label: 'Cargando imagenes...' }
    ];

    totalPromises = promesas.length;
    resolvedCount = 0;
    failedCount = 0;

    var startTime = Date.now();

    var wrappedPromises = promesas.map(function (p) {
      var wrapped = withTimeout(p.fn(), CONFIG.timeout);
      return wrapped
        .then(function (result) {
          resolvedCount++;
          updateProgress();
          console.log('[Cargador] OK', p.id, '->', result);
          return { id: p.id, status: 'ok', result: result };
        })
        .catch(function (err) {
          failedCount++;
          updateProgress();
          console.warn('[Cargador] FAIL', p.id, '->', err.message);
          return { id: p.id, status: 'fail', error: err.message };
        });
    });

    var criticas = promesas
      .map(function (p, i) { return { promise: wrappedPromises[i], meta: p }; })
      .filter(function (item) { return item.meta.critica; })
      .map(function (item) { return item.promise; });

    var noCriticas = promesas
      .map(function (p, i) { return { promise: wrappedPromises[i], meta: p }; })
      .filter(function (item) { return !item.meta.critica; })
      .map(function (item) { return item.promise; });

    Promise.all(criticas).then(function (results) {
      var elapsed = Date.now() - startTime;
      var remaining = Math.max(0, CONFIG.minDisplayTime - elapsed);

      setTimeout(function () {
        setStatus('Listo! Redirigiendo...');

        Promise.allSettled(noCriticas).then(function (settledResults) {
          var okCount = settledResults.filter(function (r) { return r.status === 'fulfilled'; }).length;
          var failCount = settledResults.filter(function (r) { return r.status === 'rejected'; }).length;
          console.log('[Cargador] No criticas:', okCount, 'ok,', failCount, 'fallidas');
        });

        setTimeout(function () {
          window.location.href = CONFIG.baseUrl + '/?from=loader';
        }, CONFIG.transitionDelay);

      }, remaining);

    }).catch(function (err) {
      console.error('[Cargador] Error inesperado:', err);
      setStatus('Error de carga. Reintentando...');
      setTimeout(function () {
        window.location.href = CONFIG.baseUrl + '/?from=loader';
      }, 1500);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciarCarga);
  } else {
    iniciarCarga();
  }

})();
</script>
<!-- Fin del bloque <script> del cargador: gestiona toda la precarga de recursos antes de abrir la tienda -->
</body>
</html>
