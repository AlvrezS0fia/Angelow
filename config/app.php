<?php
define('APP_NAME', $_ENV['APP_NAME'] ?? 'ANGELOW');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost/Angelow/public');
define('TIMEZONE', $_ENV['TIMEZONE'] ?? 'America/Bogota');
date_default_timezone_set(TIMEZONE);