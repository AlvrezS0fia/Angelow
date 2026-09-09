/* ============================================================
   ANGELOW — Promesas del Microservicio de Repartidores
   Módulo de utilidades Promise para llamadas API.
   No modifica funcionalidad existente; provee funciones
   que encapsulan fetch con timeout, retry y manejo de errores.
   ============================================================ */

var MicroservicioAPI = (function () {
  'use strict';

  var DEFAULT_TIMEOUT = 10000;
  var DEFAULT_RETRIES = 1;

  // ── Utilidad: fetch con timeout ────────────────────────────────
  function withTimeout(promise, ms) {
    return new Promise(function (resolve, reject) {
      var timer = setTimeout(function () {
        reject(new Error('Timeout después de ' + ms + 'ms'));
      }, ms);
      promise.then(
        function (val) { clearTimeout(timer); resolve(val); },
        function (err) { clearTimeout(timer); reject(err); }
      );
    });
  }

  // ── Utilidad: retry con delay ──────────────────────────────────
  function retry(fn, retries, delay) {
    return fn().catch(function (err) {
      if (retries <= 0) throw err;
      return new Promise(function (resolve) {
        setTimeout(function () { resolve(retry(fn, retries - 1, delay)); }, delay || 500);
      });
    });
  }

  // ── Utilidad: fetch base del microservicio ─────────────────────
  function microservicioFetch(url, options, config) {
    config = config || {};
    var timeout = config.timeout || DEFAULT_TIMEOUT;
    var retries = config.retries !== undefined ? config.retries : DEFAULT_RETRIES;

    var fetchFn = function () {
      return fetch(url, {
        method: options && options.method || 'GET',
        headers: options && options.headers || { 'Accept': 'application/json' },
        body: options && options.body || undefined,
      }).then(function (res) {
        return res.json().then(function (data) {
          return { status: res.status, ok: res.ok, data: data };
        });
      });
    };

    return retry(fetchFn, retries, config.retryDelay || 500)
      .then(function (result) {
        return withTimeout(Promise.resolve(result), timeout);
      })
      .catch(function (err) {
        var type = 'network';
        if (err.message && err.message.indexOf('Timeout') !== -1) type = 'timeout';
        return Promise.reject({ type: type, message: err.message });
      });
  }

  // ── Utilidad: crear imagen con promesa ─────────────────────────
  function loadImage(src) {
    return new Promise(function (resolve, reject) {
      var img = new Image();
      img.onload = function () { resolve(src); };
      img.onerror = function () { reject(new Error('Error cargando imagen: ' + src)); };
      img.src = src;
    });
  }

  // ── Utilidad: cargar stylesheet con promesa ────────────────────
  function loadStylesheet(href) {
    return new Promise(function (resolve, reject) {
      if (document.querySelector('link[href="' + href + '"]')) {
        resolve(href + ' (ya cargado)');
        return;
      }
      var link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = href;
      link.onload = function () { resolve(href); };
      link.onerror = function () { reject(new Error('Error cargando: ' + href)); };
      document.head.appendChild(link);
    });
  }

  // ── Utilidad: cargar script con promesa ────────────────────────
  function loadScript(src) {
    return new Promise(function (resolve, reject) {
      var existing = document.querySelector('script[src="' + src + '"]');
      if (existing) { resolve(src + ' (ya cargado)'); return; }
      var script = document.createElement('script');
      script.src = src;
      script.onload = function () { resolve(src); };
      script.onerror = function () { reject(new Error('Error cargando: ' + src)); };
      document.body.appendChild(script);
    });
  }

  // ── Utilidad: cargar fuente con promesa ────────────────────────
  function loadFont(family, weight) {
    return new Promise(function (resolve, reject) {
      if (document.fonts && document.fonts.check((weight || '400') + ' 16px ' + family)) {
        resolve(family + ' (ya cargada)');
        return;
      }
      var test = document.createElement('span');
      test.style.fontFamily = family;
      test.style.fontSize = '16px';
      test.style.fontWeight = weight || '400';
      test.style.position = 'absolute';
      test.style.left = '-9999px';
      test.textContent = 'Test';
      document.body.appendChild(test);
      setTimeout(function () {
        document.body.removeChild(test);
        resolve(family);
      }, 200);
    });
  }

  // ════════════════════════════════════════════════════════════════
  //  PROMESAS DE ENDPOINTS DEL MICROSERVICIO
  // ════════════════════════════════════════════════════════════════

  function loginPromise(email, password) {
    return microservicioFetch('/api/repartidor/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ email: email, password: password }),
    }, { timeout: 10000, retries: 0 });
  }

  function registroPromise(formData) {
    return microservicioFetch('/api/repartidor/registro', {
      method: 'POST',
      headers: { 'Accept': 'application/json' },
      body: formData,
    }, { timeout: 30000, retries: 0 });
  }

  function estadoPromise(email) {
    return microservicioFetch('/api/repartidor/estado?correo=' + encodeURIComponent(email), {
      method: 'GET',
      headers: { 'Accept': 'application/json' },
    }, { timeout: 10000, retries: 1 });
  }

  function dashboardMePromise(token) {
    return microservicioFetch('/api/dashboard/me', {
      method: 'GET',
      headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + token },
    }, { timeout: 10000, retries: 1 });
  }

  function dashboardResumenPromise(token) {
    return microservicioFetch('/api/dashboard/resumen', {
      method: 'GET',
      headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + token },
    }, { timeout: 15000, retries: 0 });
  }

  function aceptarPedidoPromise(token, pedidoId) {
    return microservicioFetch('/api/dashboard/pedidos/' + pedidoId + '/aceptar', {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + token },
    }, { timeout: 10000, retries: 0 });
  }

  function rechazarPedidoPromise(token, pedidoId, motivo) {
    return microservicioFetch('/api/dashboard/pedidos/' + pedidoId + '/rechazar', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': 'Bearer ' + token },
      body: JSON.stringify({ motivo: motivo || '' }),
    }, { timeout: 10000, retries: 0 });
  }

  function transicionPromise(token, pedidoId, estado, observacion) {
    return microservicioFetch('/api/dashboard/pedidos/' + pedidoId + '/estado', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': 'Bearer ' + token },
      body: JSON.stringify({ estado: estado, observacion: observacion || '' }),
    }, { timeout: 10000, retries: 0 });
  }

  function ubicacionPromise(token, data) {
    return microservicioFetch('/api/dashboard/ubicacion', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': 'Bearer ' + token },
      body: JSON.stringify(data),
    }, { timeout: 8000, retries: 0 });
  }

  function rastreoPromise(token) {
    return microservicioFetch('/api/dashboard/rastreo', {
      method: 'GET',
      headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + token },
    }, { timeout: 10000, retries: 0 });
  }

  function logoutPromise(token) {
    return microservicioFetch('/api/dashboard/logout', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': 'Bearer ' + token },
      body: JSON.stringify({ token: token || '' }),
    }, { timeout: 5000, retries: 0 });
  }

  // ════════════════════════════════════════════════════════════════
  //  API PÚBLICA
  // ════════════════════════════════════════════════════════════════

  return {
    // Utilidades
    withTimeout: withTimeout,
    microservicioFetch: microservicioFetch,
    loadImage: loadImage,
    loadStylesheet: loadStylesheet,
    loadScript: loadScript,
    loadFont: loadFont,

    // Endpoints
    login: loginPromise,
    registro: registroPromise,
    estado: estadoPromise,
    dashboardMe: dashboardMePromise,
    dashboardResumen: dashboardResumenPromise,
    aceptarPedido: aceptarPedidoPromise,
    rechazarPedido: rechazarPedidoPromise,
    transicion: transicionPromise,
    ubicacion: ubicacionPromise,
    rastreo: rastreoPromise,
    logout: logoutPromise,
  };

})();
