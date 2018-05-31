<?php
/**
 * Application Middleware
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2017 Bowling Green State University Libraries
 * @license MIT
 */

use Slim\Csrf\Guard;

// Add middleware for CSRF protection.
$app->add($container[Guard::class]);

// Redirect to HTTPS if necessary.
$app->add(function ($request, $response, $next) {
    if ($request->getUri()->getScheme() !== 'https') {
        return $response->withRedirect(
            $request->getUri()->withScheme('https')->withPort(null)
        );
    }

    return $next($request, $response);
});
