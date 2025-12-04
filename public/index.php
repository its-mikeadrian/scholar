<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

require_once __DIR__ . '/../config/bootstrap.php';

$request = Request::createFromGlobals();
$routes = require __DIR__ . '/../config/routes.php';

$context = new RequestContext();
$context->fromRequest($request);
$matcher = new UrlMatcher($routes, $context);

function render_script_to_response(string $scriptPath): Response
{
    ob_start();
    require $scriptPath;
    $content = ob_get_clean();

    $response = new Response($content);
    // Security-focused headers; complement existing app protections
    $response->headers->set('X-Frame-Options', 'DENY');
    $response->headers->set('X-Content-Type-Options', 'nosniff');
    $response->headers->set('Referrer-Policy', 'no-referrer-when-downgrade');
    $response->headers->set('Content-Security-Policy', "default-src 'self' https: data: 'unsafe-inline' 'unsafe-eval'");
    return $response;
}

try {
    $parameters = $matcher->match($request->getPathInfo());
    $script = $parameters['_script'] ?? null;

    if (!$script || !is_file($script)) {
        throw new ResourceNotFoundException('Route script not found');
    }

    // Include existing scripts. If they set headers/exit, behavior mirrors direct access.
    $response = render_script_to_response($script);
} catch (MethodNotAllowedException $e) {
    $response = new Response('Method Not Allowed', 405);
} catch (ResourceNotFoundException $e) {
    $response = new Response('Not Found', 404);
} catch (\Throwable $e) {
    // Avoid leaking details in production. Log if needed.
    $response = new Response('Internal Server Error', 500);
}

$response->send();
