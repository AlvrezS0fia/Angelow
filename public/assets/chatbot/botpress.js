// ===== CHATBOT BOTPRESS - LOADER =====
(function() {
  'use strict';
  
  console.log('🚀 Iniciando carga del chatbot...');
  
  // Función para intentar abrir el chat
  function forceOpenChat() {
    if (typeof window.botpress !== 'undefined') {
      console.log('✅ Botpress encontrado, abriendo...');
      window.botpress.open();
      return true;
    }
    return false;
  }
  
  // Cargar el script principal de Botpress
  const mainScript = document.createElement('script');
  mainScript.src = 'https://cdn.botpress.cloud/webchat/v3.6/inject.js';
  mainScript.onload = function() {
    console.log('✅ Script inject.js cargado');
    
    // Cargar la configuración específica del bot
    const configScript = document.createElement('script');
    configScript.src = 'https://files.bpcontent.cloud/2025/10/06/17/20251006173617-J1FBKP60.js';
    configScript.onload = function() {
      console.log('✅ Configuración del bot cargada');
      
      // Intentar abrir inmediatamente
      forceOpenChat();
      
      // Intentar cada 500ms durante 10 segundos
      let attempts = 0;
      const interval = setInterval(function() {
        attempts++;
        if (forceOpenChat()) {
          clearInterval(interval);
        } else if (attempts >= 20) {
          console.log('⚠️ No se pudo abrir el chat automáticamente');
          clearInterval(interval);
        }
      }, 500);
    };
    configScript.onerror = function() {
      console.error('❌ Error cargando la configuración del bot');
    };
    document.head.appendChild(configScript);
  };
  mainScript.onerror = function() {
    console.error('❌ Error cargando inject.js');
  };
  document.head.appendChild(mainScript);
})();