<?php
/*
 |========================================================================
 | VISTA: layouts/leaflet-css.php
 |------------------------------------------------------------------------
 | QUÉ MUESTRA: No renderiza secciones visibles; solo declara los enlaces
 | CDN de CSS de Leaflet (mapa, routing, marker cluster y decoradores)
 | que otras vistas incluyen en su <head> para usar mapas.
 |
 | ARCHIVOS EXTERNOS: Leaflet 1.9.4, Leaflet Routing Machine, Marker
 | Cluster y Leaflet PolylineDecorator vía CDN de unpkg/cdnjs.
 |
 | JS QUE LA CONTROLA: Ninguno; es solo CSS. Su contraparte JS es
 | layouts/leaflet-js.php.
 |========================================================================
*/
// ============================================================================
// ANGELOW — Assets compartidos de LEAFLET (CSS)
// ----------------------------------------------------------------------------
// Fuente única de los <link> CDN del mapa. Incluir en <head> de cualquier vista
// que use Leaflet (cliente, admin, repartidor). Elimina el bloque CDN duplicado
// en 4+ archivos.
// ============================================================================
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet-polylinedecorator/1.6.0/leaflet.polylineDecorator.css" />
