<?php

use Beam\Pipe;
use Beam\Router;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

/** @var $c ContainerInterface */

return new Pipe(
    new ErrorHandler,
    $c->get(ErrorHandler::class),
    new ForceHttps,
    new PrefixDispatcher([
        '/api/' => new Pipe(
            new Authentication(),
            (new Router)
                ->get( '/api/articles',      '' /* controller */)
                ->post('/api/articles/{id}', '' /* controller */)
        ),

        '/dashboard/' => require __DIR__.'/dashboard.php',

        '/' => new Pipe(
            new MaintenanceMode,
            new SessionMiddleware,
            new DebugBar,
            /** @var Router */
            $c->get(Router::class)
                ->get( '/',          '' /* controller */)
                ->post('/dashboard', '' /* controller */)
        ),
    ])
);


class ErrorHandler extends Fake {}
class ForceHttps extends Fake {}
class Authentication extends Fake {}
class PrefixDispatcher extends Fake {function __construct(...$params){}}
class MaintenanceMode extends Fake {}
class SessionMiddleware extends Fake {}
class DebugBar extends Fake {}
class Fake implements MiddlewareInterface {public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {}}
