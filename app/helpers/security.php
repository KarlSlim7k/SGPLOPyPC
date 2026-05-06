<?php
declare(strict_types=1);

function setSecurityHeaders(): void {
    // Evita que el navegador "adivine" el tipo de contenido (mitiga MIME-sniffing)
    header('X-Content-Type-Options: nosniff');
    // Previene clickjacking al no permitir que la página se embeba en frames
    header('X-Frame-Options: DENY');
    // Política de referrer mínima
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // Política de permisos de características
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    // CSP básico viable en arquitectura vanilla (permite CDN de fuentes, Tailwind, Phosphor)
    $csp = "default-src 'self'; "
         . "script-src 'self' https://cdn.tailwindcss.com https://unpkg.com 'unsafe-inline'; "
         . "style-src 'self' https://fonts.googleapis.com https://cdn.tailwindcss.com 'unsafe-inline'; "
         . "font-src 'self' https://fonts.gstatic.com; "
         . "img-src 'self' data:; "
         . "connect-src 'self'; "
         . "frame-ancestors 'none'; "
         . "base-uri 'self'; "
         . "form-action 'self';";
    header('Content-Security-Policy: ' . $csp);
}
