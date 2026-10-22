<?php

if (!defined('BASE_URL')) {
    define('BASE_URL', '');
}


function e($texto): string
{
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}

/** URL de una vista del sistema: ruta('catalogo') o ruta('catalogo', ['q'=>'serum']) */
function ruta(string $vista, array $params = []): string
{
    $url = BASE_URL . '/index.php?v=' . urlencode($vista);
    foreach ($params as $k => $val) {
        if ($val === null || $val === '') {
            continue;
        }
        $url .= '&' . urlencode((string)$k) . '=' . urlencode((string)$val);
    }
    return $url;
}

function csrf(): string
{
    return \App\Core\Csrf::campo();
}


function confirmar(string $mensaje): string
{
    return ' data-confirm="' . htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') . '"';
}

/** Mensajes flash (éxito / error) en la parte superior de la vista. */
function flashes(): string
{
    $flash = \App\Core\Flash::pull();
    $ok = $flash['ok'] ?? [];
    $err = $flash['err'] ?? [];
    $html = '';
    foreach ($ok as $m) {
        $html .= '<div class="alert ok">' . icon('check', 18) . '<span>' . e($m) . '</span></div>';
    }
    foreach ($err as $m) {
        $html .= '<div class="alert warn">' . e($m) . '</div>';
    }
    return $html;
}

/** Errores de validación de un campo concreto. */
function error_campo(array $errores, string $campo): string
{
    if (!empty($errores[$campo][0])) {
        return '<span class="field-error">' . e($errores[$campo][0]) . '</span>';
    }
    return '';
}

/** Clase CSS de error para un campo. */
function clase_campo(array $errores, string $campo): string
{
    return !empty($errores[$campo]) ? ' input-error' : '';
}

/**
 * Controles de paginación. Devuelve cadena vacía si todo cabe en una página.
 */
function paginacion(string $vista, array $params, int $total, int $pagina, int $porPagina): string
{
    $paginas = (int)ceil($total / $porPagina);
    if ($paginas <= 1) {
        return '';
    }
    $pagina = max(1, min($pagina, $paginas));
    $anterior = max(1, $pagina - 1);
    $siguiente = min($paginas, $pagina + 1);

    $html = '<div class="pager">';
    $html .= $pagina > 1
        ? '<a class="page-link" href="' . e(ruta($vista, $params + ['p' => $anterior])) . '">&lsaquo; Anterior</a>'
        : '<span class="page-link off">&lsaquo; Anterior</span>';

    $inicio = max(1, $pagina - 2);
    $fin = min($paginas, $pagina + 2);
    for ($i = $inicio; $i <= $fin; $i++) {
        $html .= $i === $pagina
            ? '<span class="page-link active">' . $i . '</span>'
            : '<a class="page-link" href="' . e(ruta($vista, $params + ['p' => $i])) . '">' . $i . '</a>';
    }

    $html .= $pagina < $paginas
        ? '<a class="page-link" href="' . e(ruta($vista, $params + ['p' => $siguiente])) . '">Siguiente &rsaquo;</a>'
        : '<span class="page-link off">Siguiente &rsaquo;</span>';
    $html .= '<span class="page-count">' . $total . ' registros · ' . $paginas . ' págs.</span>';
    $html .= '</div>';
    return $html;
}

/** Estado vacío cuando una tabla/lista no tiene datos. */
function vacio(string $mensaje, string $icono = 'search'): string
{
    return '<div class="empty">' . icon($icono, 40) . '<p>' . e($mensaje) . '</p></div>';
}

/** Insignia de estado de una reserva o compra. */
function estado(string $e): string
{
    $k = strtolower($e);
    return '<span class="badge-st st-' . e($k) . '">' . e(ucfirst($e)) . '</span>';
}

/** Formato de dinero: Bs. 1,234.56 */
function bs($monto): string
{
    return 'Bs. ' . number_format((float)$monto, 2);
}

/** Dibujo de los labios del logo (manual de marca). */
function lips(string $clase = ''): string
{
    return '<svg class="' . $clase . '" viewBox="0 0 120 80" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <path d="M8 38c14-3 22-18 34-24 8-4 12 0 18 0s10-4 18 0c12 6 20 21 34 24" stroke="#54458e" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
      <path d="M12 40c20 8 36 10 48 2 12 8 28 6 48-2-6 22-22 36-48 36S18 62 12 40Z" fill="#a47cb8" stroke="#54458e" stroke-width="5" stroke-linejoin="round"/>
      <path d="M12 40c20-8 36-10 48-2 12-8 28-6 48 2" stroke="#54458e" stroke-width="5" stroke-linecap="round"/>
    </svg>';
}

/** Logo de Tatiana: palabra en script con labios y subtítulo. */
function logo(bool $claro = false): string
{
    $c = $claro ? 'light' : '';
    return '<a href="' . ruta('home') . '" class="logo ' . $c . '">
        <span class="logo-word">Tati' . lips() . 'na</span>
        <span class="logo-sub">SKIN AND BEAUTY</span>
    </a>';
}

/** Íconos de línea (estilo Feather) en SVG. */
function icon(string $n, int $s = 22): string
{
    $p = [
        'home'     => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>',
        'users'    => '<circle cx="9" cy="8" r="4"/><path d="M2 21c0-4 3-7 7-7s7 3 7 7"/><path d="M16 4a4 4 0 010 8"/><path d="M22 21c0-3-2-5-4-6"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'cart'     => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.5 12h11L21 7H6"/>',
        'box'      => '<path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-7 8-7s8 3 8 7"/>',
        'chart'    => '<path d="M4 20V10M10 20V4M16 20v-8M22 20H2"/>',
        'logout'   => '<path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/>',
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
        'bell'     => '<path d="M6 8a6 6 0 0112 0c0 7 3 8 3 8H3s3-1 3-8"/><path d="M10 21a2 2 0 004 0"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'edit'     => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>',
        'trash'    => '<path d="M3 6h18M8 6V4h8v2M6 6l1 15h10l1-15"/>',
        'eye'      => '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/>',
        'check'    => '<path d="M5 12l5 5L20 7"/>',
        'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'sparkle'  => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3z"/><path d="M19 16l.8 2.2L22 19l-2.2.8L19 22l-.8-2.2L16 19l2.2-.8L19 16z"/>',
        'drop'     => '<path d="M12 3s7 7 7 12a7 7 0 01-14 0c0-5 7-12 7-12z"/>',
        'heart'    => '<path d="M20.8 5.6a5 5 0 00-7.1 0L12 7.3l-1.7-1.7a5 5 0 00-7.1 7.1L12 21.5l8.8-8.8a5 5 0 000-7.1z"/>',
        'shield'   => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z"/><path d="M9 12l2 2 4-4"/>',
        'upload'   => '<path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v3a2 2 0 002 2h12a2 2 0 002-2v-3"/>',
        'print'    => '<path d="M6 9V3h12v6M6 18H4a1 1 0 01-1-1v-6a2 2 0 012-2h14a2 2 0 012 2v6a1 1 0 01-1 1h-2"/><rect x="6" y="14" width="12" height="7"/>',
        'pin'      => '<path d="M12 22s7-6.5 7-12a7 7 0 10-14 0c0 5.5 7 12 7 12z"/><circle cx="12" cy="10" r="2.5"/>',
        'phone'    => '<path d="M5 3h4l2 5-2.5 1.5a11 11 0 006 6L16 13l5 2v4a2 2 0 01-2 2A16 16 0 013 5a2 2 0 012-2z"/>',
        'menu'     => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'leaf'     => '<path d="M5 21c0-9 5-16 16-17-1 11-8 16-16 17z"/><path d="M5 21c3-5 6-8 10-10"/>',
        'filter'   => '<path d="M3 5h18l-7 8v6l-4-2v-4L3 5z"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 11-4 0v-.09A1.65 1.65 0 008 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06A1.65 1.65 0 004.6 15a1.65 1.65 0 00-1.51-1H3a2 2 0 110-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06A1.65 1.65 0 009 4.6a1.65 1.65 0 001-1.51V3a2 2 0 114 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06A1.65 1.65 0 0019.4 9c.14.36.4.66.74.86.3.18.65.28 1 .28H21a2 2 0 110 4h-.09a1.65 1.65 0 00-1.51 1z"/>',
        'file'     => '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/>',
        'key'      => '<path d="M21 2l-2 2m-7.6 7.6a5.5 5.5 0 11-7.8 7.8 5.5 5.5 0 017.8-7.8zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>',
        'download' => '<path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><path d="M7 10l5 5 5-5M12 15V3"/>',
        'message'  => '<path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>',
        'history'  => '<path d="M3 3v5h5"/><path d="M3.05 13A9 9 0 106 5.3L3 8"/><path d="M12 7v5l4 2"/>',
    ];
    $d = $p[$n] ?? $p['sparkle'];
    return '<svg width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
}

/** Dibujo genérico de frasco para productos sin imagen. */
function frasco(): string
{
    return '<svg viewBox="0 0 60 80" fill="none" stroke="#54458e" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
      <rect x="22" y="4" width="16" height="10" rx="3" fill="#fff"/><path d="M26 14v6M34 14v6"/>
      <rect x="12" y="20" width="36" height="54" rx="10" fill="#fff" fill-opacity=".75"/>
      <path d="M20 44c6-4 14 4 20 0M20 54c6-4 14 4 20 0" opacity=".6"/></svg>';
}
