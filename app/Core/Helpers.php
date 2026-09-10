<?php
/**
 * ============================================================
 * ARCHIVO: Helpers.php — MÓDULO: Funciones auxiliares globales
 * ============================================================
 * QUÉ HACE: Funciones de utilidad sin pertenencia a una clase.
 *           sanitize(): limpia entrada de usuario contra XSS.
 */

/** Limpia una cadena de texto para prevenir inyección de HTML/JS (XSS). */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}