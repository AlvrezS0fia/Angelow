<?php
define('APP_NAME', $_ENV['APP_NAME'] ?? 'ANGELOW');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost/Angelow/public');
define('MICROSERVICE_URL', $_ENV['MICROSERVICE_URL'] ?? 'http://127.0.0.1:8000/');
/**
 * URL base del microservicio de INVENTARIO de ANGELOW (Spring Boot + JPA, puerto 8082).
 * Todas las conexiones PHP -> microservicio salen de ESTA constante (se define
 * una sola vez aquí y se lee del .env con PRODUCTOS_API_URL). Se usa en:
 *   - app/Libraries/ProductoMicroservice.php (cliente PHP del inventario Inventario2)
 *   - app/Views/admin/inventario2.php (JS que consulta al microservicio)
 * NUNCA se escribe la URL "a mano" en otras partes: todo pasa por PRODUCTOS_API_URL.
 */
define('PRODUCTOS_API_URL', $_ENV['PRODUCTOS_API_URL'] ?? 'http://localhost:8082/api/inventario');
define('TIMEZONE', $_ENV['TIMEZONE'] ?? 'America/Bogota');
date_default_timezone_set(TIMEZONE);