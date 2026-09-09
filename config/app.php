<?php
define('APP_NAME', $_ENV['APP_NAME'] ?? 'ANGELOW');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost/Angelow/public');
define('MICROSERVICE_URL', $_ENV['MICROSERVICE_URL'] ?? 'http://127.0.0.1:8000/');
define('TIMEZONE', $_ENV['TIMEZONE'] ?? 'America/Bogota');
date_default_timezone_set(TIMEZONE);