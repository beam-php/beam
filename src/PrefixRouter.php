<?php

namespace Beam;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class PrefixRouter implements MiddlewareInterface
{
    /** @var ResponseFactoryInterface */
    private $factory;

    /** @var Router[] */
    private $routers;

    /**
     * @param Router[] $routers
     * @param ResponseFactoryInterface|null $factory
     */
    public function __construct(array $routers = [], ResponseFactoryInterface $factory = null)
    {
        $this->factory = $factory;
        $this->routers = $routers;
    }

    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        foreach ($this->routers as $prefix => $router) {
            if (0 === strpos($request->getUri()->getPath(), $prefix)) {
                return $router->process($request, $handler);
            }
        }

        return $this->factory->createResponse(404);
    }
}
