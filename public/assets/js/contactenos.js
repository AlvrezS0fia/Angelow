document.addEventListener('DOMContentLoaded', function () {
  // Año en footer
  document.getElementById('currentYear').textContent = new Date().getFullYear();

  const form = document.getElementById('contactForm');
  const msgDiv = document.getElementById('formMessage');

  // Reglas de validación
  const rules = {
    nombre:   (v) => v.trim().length >= 3,
    email:    (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()),
    telefono: (v) => /^[\+\d\s\-]{7,}$/.test(v.trim()),
    asunto:   (v) => v.trim().length >= 4,
    mensaje:  (v) => v.trim().length >= 10
  };

  function showFieldError(id, isValid) {
    const errDiv = document.getElementById('err-' + id);
    if (errDiv) errDiv.style.display = isValid ? 'none' : 'block';
  }

  function validateField(id) {
    const input = document.getElementById(id);
    const isValid = rules[id](input.value);
    showFieldError(id, isValid);
    return isValid;
  }

  // Agregar eventos blur a cada campo
  Object.keys(rules).forEach(id => {
    const input = document.getElementById(id);
    if (input) {
      input.addEventListener('blur', () => validateField(id));
      input.addEventListener('input', () => {
        if (input.classList.contains('error') || document.getElementById('err-' + id)?.style.display === 'block') {
          validateField(id);
        }
      });
    }
  });

  // Envío del formulario
  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Validar todos los campos
    let allValid = true;
    Object.keys(rules).forEach(id => {
      if (!validateField(id)) allValid = false;
    });
    if (!allValid) return;

    const data = {
      nombre:   document.getElementById('nombre').value.trim(),
      email:    document.getElementById('email').value.trim(),
      telefono: document.getElementById('telefono').value.trim(),
      asunto:   document.getElementById('asunto').value.trim(),
      mensaje:  document.getElementById('mensaje').value.trim()
    };

    const submitBtn = form.querySelector('.btn-submit');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = 'Enviando... <i class="fas fa-spinner fa-spin"></i>';

    try {
      const response = await fetch(APP_URL + '/contacto/enviar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      });
      const result = await response.json();

      if (result.success) {
        showMessage(result.message, true);
        form.reset();
        // Limpiar estilos de validación
        Object.keys(rules).forEach(id => showFieldError(id, true));
      } else {
        showMessage(result.message, false);
      }
    } catch (err) {
      showMessage('Error de conexión. Intenta de nuevo.', false);
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    }
  });

  function showMessage(msg, isSuccess) {
    msgDiv.style.display = 'block';
    msgDiv.className = isSuccess ? 'form-message success' : 'form-message error';
    msgDiv.innerHTML = '<i class="fas ' + (isSuccess ? 'fa-check-circle' : 'fa-exclamation-triangle') + '"></i> ' + msg;
    setTimeout(() => {
      msgDiv.style.display = 'none';
    }, 5000);
  }
});