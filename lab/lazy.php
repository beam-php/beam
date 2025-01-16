<?php

return [Pipe::class, [
    ErrorHandler::class,
    ForceHttps::class,
    PrefixDispatcher::class => [
        '/api/' => [Pipe::class, [
            Authentication::class,
            [ Dispatcher::class, [
                '/api/articles' => ''/* controller */,
                '/api/articles/{id}' => ''/* controller */,
            ]],
        ]],

        '/dashboard/' => require __DIR__.'/dashboard.php',

        '/' => [Pipe::class, [
            MaintenanceMode::class,
            SessionMiddleware::class,
            DebugBar::class,
            [ Dispatcher::class, [
                '/' => ''/* controller */,
                '/article/{id}' => ''/* controller */,
            ]],
        ]],
    ]
]];

class Pipe {function __construct(...$params){}}
class ErrorHandler {}
class ForceHttps {}
class Authentication {}
class PrefixDispatcher {function __construct(...$params){}}
class Dispatcher {function __construct(...$params){}}
class MaintenanceMode {}
class SessionMiddleware {}
class DebugBar {}
