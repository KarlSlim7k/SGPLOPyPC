<?php
declare(strict_types=1);

function dispatchPublicRouteTable(string $route, string $requestMethod, Logger $logger): bool {
    $publicController = null;
    $authController = null;

    $public = static function () use (&$publicController): PublicController {
        if ($publicController === null) {
            $publicController = new PublicController();
        }
        return $publicController;
    };

    $auth = static function () use (&$authController): AuthController {
        if ($authController === null) {
            $authController = new AuthController();
        }
        return $authController;
    };

    $exactRoutes = [
        'POST /auth/password/forgot' => static function () use ($auth, $logger): void {
            $rl = new RateLimiter(
                (int) env('RATE_LIMIT_FORGOT_MAX', '5'),
                (int) env('RATE_LIMIT_FORGOT_WINDOW', '300')
            );
            $ip = getClientIp();
            if (!$rl->isAllowed('auth-forgot:' . $ip)) {
                $logger->security('Rate limit exceeded on forgot password', ['ip' => $ip]);
                jsonResponse(false, 'Demasiadas solicitudes de recuperación. Intente más tarde.', null, null, 429);
            }
            $auth()->forgotPassword();
        },
        'POST /auth/password/reset' => static function () use ($auth, $logger): void {
            $rl = new RateLimiter(
                (int) env('RATE_LIMIT_RESET_MAX', '10'),
                (int) env('RATE_LIMIT_RESET_WINDOW', '300')
            );
            $ip = getClientIp();
            if (!$rl->isAllowed('auth-reset:' . $ip)) {
                $logger->security('Rate limit exceeded on reset password', ['ip' => $ip]);
                jsonResponse(false, 'Demasiadas solicitudes de restablecimiento. Intente más tarde.', null, null, 429);
            }
            $auth()->resetPassword();
        },
        'GET /public/convocatorias' => static function () use ($public, $logger): void {
            enforcePublicReadRateLimit($logger, 'convocatorias');
            $public()->listConvocatorias();
        },
        'GET /public/resultados' => static function () use ($public, $logger): void {
            enforcePublicReadRateLimit($logger, 'resultados');
            $public()->listResultados();
        },
        'GET /public/contratos' => static function () use ($public, $logger): void {
            enforcePublicReadRateLimit($logger, 'contratos');
            $public()->listContratos();
        },
        'GET /public/evaluaciones' => static function () use ($public, $logger): void {
            enforcePublicReadRateLimit($logger, 'evaluaciones');
            $public()->listEvaluaciones();
        },
        'GET /public/historial' => static function () use ($public, $logger): void {
            enforcePublicReadRateLimit($logger, 'historial');
            $public()->listHistorial();
        },
        'GET /public/estadisticas' => static function () use ($public, $logger): void {
            enforcePublicReadRateLimit($logger, 'estadisticas');
            $public()->estadisticas();
        },
        'POST /public/proveedores/registro' => static function () use ($public, $logger): void {
            $rl = new RateLimiter(
                (int) env('RATE_LIMIT_PUBLIC_REGISTER_MAX', '5'),
                (int) env('RATE_LIMIT_PUBLIC_REGISTER_WINDOW', '300')
            );
            $ip = getClientIp();
            if (!$rl->isAllowed('public-register:' . $ip)) {
                $logger->security('Rate limit exceeded on public register', ['ip' => $ip]);
                jsonResponse(false, 'Demasiados intentos de registro. Intente más tarde.', null, null, 429);
            }
            $public()->registerProveedor();
        },
        'POST /public/soporte' => static function () use ($public, $logger): void {
            $rl = new RateLimiter(
                (int) env('RATE_LIMIT_PUBLIC_SUPPORT_MAX', '5'),
                (int) env('RATE_LIMIT_PUBLIC_SUPPORT_WINDOW', '300')
            );
            $ip = getClientIp();
            if (!$rl->isAllowed('public-support:' . $ip)) {
                $logger->security('Rate limit exceeded on support', ['ip' => $ip]);
                jsonResponse(false, 'Demasiadas solicitudes de soporte. Intente más tarde.', null, null, 429);
            }
            $public()->supportTicket();
        },
    ];

    $exactKey = $requestMethod . ' ' . $route;
    if (isset($exactRoutes[$exactKey])) {
        $exactRoutes[$exactKey]();
        return true;
    }

    $patternRoutes = [
        [
            'method' => 'GET',
            'pattern' => '#^/public/convocatorias/(\d+)$#',
            'handler' => static function (array $m) use ($public, $logger): void {
                enforcePublicReadRateLimit($logger, 'convocatorias-detalle');
                $public()->getConvocatoria((int) $m[1]);
            },
        ],
        [
            'method' => 'GET',
            'pattern' => '#^/public/convocatorias/(\d+)/documentos$#',
            'handler' => static function (array $m) use ($public): void {
                $public()->listConvocatoriaDocumentos((int) $m[1]);
            },
        ],
        [
            'method' => 'GET',
            'pattern' => '#^/public/documentos/(\d+)/download$#',
            'handler' => static function (array $m) use ($public): void {
                $public()->downloadDocumentoPublico((int) $m[1]);
            },
        ],
    ];

    foreach ($patternRoutes as $patternRoute) {
        if ($requestMethod !== $patternRoute['method']) {
            continue;
        }
        if (preg_match($patternRoute['pattern'], $route, $matches) !== 1) {
            continue;
        }
        $patternRoute['handler']($matches);
        return true;
    }

    return false;
}
