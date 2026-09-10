<?php
/*
 |========================================================================
 | VISTA: layouts/leaflet-js.php
 |------------------------------------------------------------------------
 | QUÉ MUESTRA: No renderiza secciones visibles; solo declara los <script>
 | CDN de Leaflet (mapa, routing, marker cluster y decoradores) que otras
 | vistas incluyen para cargar la librería del mapa.
 |
 | ARCHIVOS EXTERNOS: Leaflet 1.9.4, Leaflet Routing Machine, Marker
 | Cluster y Leaflet PolylineDecorator vía CDN de unpkg/cdnjs.
 |
 | JS QUE LA CONTROLA: Las librerías que carga habilitan los mapas en las
 | vistas; el control real de cada mapa está en el JS de la vista que
 | incluye este layout.
 |========================================================================
*/
// ============================================================================
// ANGELOW — Assets compartidos de LEAFLET (JS)
// ----------------------------------------------------------------------------
// Fuente única de los <script> CDN del mapa. Incluir justo donde antes estaba el
// bloque de scripts de Leaflet (mantiene el orden de carga de cada vista).
// ============================================================================
?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet-polylinedecorator/1.6.0/leaflet.polylineDecorator.js"></script>
