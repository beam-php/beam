<?php

declare(strict_types=1);

namespace Beam;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @method $this get(string $path, MiddlewareInterface $handler)
 * @method $this head(string $path, MiddlewareInterface $handler)
 * @method $this post(string $path, MiddlewareInterface $handler)
 * @method $this put(string $path, MiddlewareInterface $handler)
 * @method $this delete(string $path, MiddlewareInterface $handler)
 * @method $this options(string $path, MiddlewareInterface $handler)
 */
final class Router implements MiddlewareInterface
{
    /** @var MiddlewareInterface[][] */
    private $routes = [];

    /** @var ResponseFactoryInterface */
    private $factory;

    public function __construct(ResponseFactoryInterface $factory = null)
    {
        $this->factory = $factory ?? Locator::instance()->getResponseFactory();
    }

    public function __call($name, $arguments)
    {
        if (!in_array($name, ['get', 'head', 'post', 'put', 'delete', 'options'])) {
            throw new \BadMethodCallException('Unknown HTTP method "'.$name.'".');
        }

        list($path, $handler) = $arguments;
        $this->routes[strtoupper($name)][$this->pathToRegex($path)] = $handler;

        return $this;
    }

    private function pathToRegex(string $path): string
    {
        $regex = $path;
        $replacements = [];
        preg_match_all('/{([^}:]+)(:([^}]+))?}/u', $path, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            if (!isset($match[3])) {
                $pattern = '[^/]+';
            } else {
                $pattern = $match[3];
            }
            $replacements[$match[0]] = sprintf('(?<%s>%s)', $match[1], $pattern);
        }

        return '$' .  str_replace(array_keys($replacements), array_values($replacements), $regex) . '$';
    }

    /**
     * Keep only named matches from a preg_match() matches array.
     *
     * Example:
     *
     * Array
     * (
     *     [0] => foo bar baz
     *     [wat] => foo
     *     [1] => foo
     *     [wut] => bar
     *     [2] => bar
     *     [ok] => baz
     *     [3] => baz
     * )
     *   turns into:
     *
     * Array
     * (
     *     [wat] => foo
     *     [wut] => bar
     *     [ok] => baz
     * )
     *
     * @param array $matches
     * @return array
     */
    private function keepRegexNamedMatches(array $matches): array
    {
        $keys = range(0, count($matches) >> 1);

        return array_diff_key($matches, array_combine($keys, $keys));
    }

    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     *
     * @see https://github.com/for-GET/http-decision-diagram
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!isset($this->routes[$request->getMethod()])) {
            return $this->factory->createResponse(405);
        }

        foreach ($this->routes[$request->getMethod()] as $regex => $callable) {
            if (preg_match($regex, $request->getUri()->getPath(), $matches)) {
                foreach ($this->keepRegexNamedMatches($matches) as $name => $value) {
                    $request = $request->withAttribute($name, $value);
                }

                return $callable($request);
            }
        }

        return $this->factory->createResponse(404);
    }
}
