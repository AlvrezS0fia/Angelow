<?php
/**
 * ============================================================
 * ARCHIVO: LogoutController.php — MÓDULO: Script de cierre de sesión (legacy)
 * ============================================================
 * QUÉ HACE: Destruye la sesión activa del usuario y redirige al login.
 * MODELO(S) QUE USA: ninguno
 * ENDPOINTS/RUTAS: acceso directo vía URL (GET logout.php)
 * QUIÉN LO CONSUME: Botón "Cerrar sesión" de las vistas legacy y del panel
 *   de cuenta (mi cuenta / dashboard).
 */
// logout.php
session_start();
session_destroy();
header('Location: login.php');
exit();
?>