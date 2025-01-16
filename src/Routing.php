<?php

namespace Beam;

use Interop\Routing\DispatcherInterface;
use Interop\Routing\Factory;
use Interop\Routing\Route\RouteCollection;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Routing extends RouteCollection implements MiddlewareInterface
{
    private ?ResponseFactoryInterface $responseFactory;
    private ?DispatcherInterface $dispatcher;

    public function __construct(ResponseFactoryInterface $responseFactory = null, DispatcherInterface $dispatcher = null)
    {
        $this->responseFactory = $responseFactory;
        $this->dispatcher = $dispatcher;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->responseFactory->createResponse()->withBody($this->dispatcher->dispatch($request)());
    }

    private function getDispatcher(): DispatcherInterface
    {
        if (null === $this->dispatcher) {
            $this->dispatcher = (new Factory)->create(null, $this);
        }

        return $this->dispatcher;
    }
}
