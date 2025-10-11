<?php

declare(strict_types=1);

namespace App\Http;

use App\Security\JwtAuth;
use App\Support\ResponseFactory;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function FastRoute\simpleDispatcher;

final class Kernel
{
    private Dispatcher $dispatcher;

    public function __construct()
    {
        $this->dispatcher = simpleDispatcher(function (RouteCollector $r) {
            $r->addRoute('OPTIONS', '/{any:.*}', fn () => new Response('', 204));
            $r->addRoute('POST', '/api/auth/login', [Controllers\AuthController::class, 'login']);
            $r->addRoute('GET', '/api/jobs', [Controllers\JobsController::class, 'list']);
            $r->addRoute('GET', '/api/customers', [Controllers\CustomersController::class, 'list']);
            $r->addRoute('POST', '/api/media/upload', [Controllers\MediaController::class, 'upload']);
            $r->addRoute('GET', '/api/invoices', [Controllers\InvoicesController::class, 'list']);
            $r->addRoute('GET', '/api/promotions', [Controllers\PromotionsController::class, 'list']);
            $r->addRoute('GET', '/api/reviews', [Controllers\ReviewsController::class, 'list']);
            $r->addRoute('GET', '/api/analytics/summary', [Controllers\AnalyticsController::class, 'summary']);
            $r->addRoute('GET', '/api/settings', [Controllers\SettingsController::class, 'get']);
            $r->addRoute('PUT', '/api/settings', [Controllers\SettingsController::class, 'update']);
        });
    }

    public function handle(Request $request): Response
    {
        $response = $this->route($request);
        $this->addSecurityHeaders($response);
        $this->addCors($request, $response);
        return $response;
    }

    private function route(Request $request): Response
    {
        $routeInfo = $this->dispatcher->dispatch($request->getMethod(), $request->getPathInfo());

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                return ResponseFactory::json(['message' => 'Not found'], 404);
            case Dispatcher::METHOD_NOT_ALLOWED:
                return ResponseFactory::json(['message' => 'Method not allowed'], 405);
            case Dispatcher::FOUND:
                [$class, $method] = $routeInfo[1];
                $vars = $routeInfo[2];
                $controller = new $class();
                // Guard routes that are not auth endpoints
                if (!($controller instanceof Controllers\AuthController)) {
                    $auth = new JwtAuth();
                    $user = $auth->authenticateFromRequest($request);
                    if ($user === null) {
                        return ResponseFactory::json(['message' => 'Unauthorized'], 401);
                    }
                    $request->attributes->set('user', $user);
                }
                return $controller->$method($request, $vars);
        }

        return ResponseFactory::json(['message' => 'Unhandled'], 500);
    }

    private function addCors(Request $request, Response $response): void
    {
        $origin = $request->headers->get('Origin');
        $allowedOrigins = array_filter(array_map('trim', explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? 'http://localhost:5173')));
        $isAllowed = $origin !== null && in_array($origin, $allowedOrigins, true);

        if ($isAllowed) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        $response->headers->set('Vary', 'Origin');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Max-Age', '600');
    }

    private function addSecurityHeaders(Response $response): void
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $response->headers->set('Content-Security-Policy', "default-src 'none'; script-src 'self'; connect-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; base-uri 'self'; frame-ancestors 'self'");
    }
}
