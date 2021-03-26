<?php

namespace Beam;

use Beam\Locator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use BadMethodCallException;
use Middlewares\Emitter;

class Pipe implements MiddlewareInterface, RequestHandlerInterface
{
    /** @var MiddlewareInterface[] */
    private $middlewares;

    /** @var ServerRequestFactoryInterface */
    private $serverRequestFactory;

    /** @var ResponseFactoryInterface */
    private $responseFactory;

    /** @var UriFactoryInterface */
    private $uriFactory;

    /** @var StreamFactoryInterface */
    private $streamFactory;

    /** @var ResponseInterface */
    private $response;

    /** @var ContainerInterface */
    private $container;

    /* @var RequestHandlerInterface|null */
    private $next;

    /** @param MiddlewareInterface[] $middlewares */
    public function __construct(MiddlewareInterface ...$middlewares)
    {
        $this->middlewares = $middlewares;
    }

    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $this->next = $next;
        return $this->dispatch($request);
    }

    private function getGlobalRequest(): GlobalRequest
    {
        static $request;

        return $request ? $request : $request = new GlobalRequest(
            $this->serverRequestFactory,
            $this->uriFactory,
            $this->streamFactory
        );
    }

    /**
     * Return the next available middleware in the stack.
     *
     * @return MiddlewareInterface|false
     */
    private function get(ServerRequestInterface $request)
    {
        $middleware = current($this->middlewares);
        next($this->middlewares);

        return $middleware;
    }

    /**
     * Dispatch the request, return a response.
     */
    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        if (is_null($request)) {
            $request = Locator::instance()->getServerRequest();
        }
        reset($this->middlewares);

        return $this->handle($request);
    }

    public function run(ServerRequestInterface $request = null): void
    {
        (new Emitter)->process(
            $request ?? Locator::instance()->getServerRequest(),
            $this
        );
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = $this->get($request);
        if ($middleware === false) {
            if ($this->next !== null) {
                return $this->next->handle($request);
            }
            throw new LogicException('Middleware queue exhausted');
        }

        return $middleware->process($request, $this);
    }
}
