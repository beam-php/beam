<?php

namespace Beam;

use Psr\Http\Server\MiddlewareInterface;

final class Beam
{
    /** @var MiddlewareInterface[] */
    private $middlewares;

    public function __construct(MiddlewareInterface... $middlewares)
    {
        $this->middlewares = $middlewares;
    }

    public function run()
    {

    }
}
